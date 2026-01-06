const path = require('path');
const fs = require('fs');
const express = require('express');
const axios = require('axios');
const pino = require('pino');
const dotenv = require('dotenv');
const {
  default: makeWASocket,
  useMultiFileAuthState,
  fetchLatestBaileysVersion,
  DisconnectReason,
} = require('@whiskeysockets/baileys');

dotenv.config({ path: path.join(__dirname, '.env') });

dotenv.config({ path: path.join(__dirname, '.env.local') });

dotenv.config();

const app = express();
app.use(express.json());

const PORT = process.env.PORT || 3001;
const PANEL_BASE_URL = process.env.PANEL_BASE_URL || 'http://127.0.0.1:8000';
const WEBHOOK_TOKEN = process.env.WHATSAPP_WEBHOOK_TOKEN || '';
const GATEWAY_TOKEN = process.env.WHATSAPP_GATEWAY_TOKEN || '';
const LOG_LEVEL = process.env.LOG_LEVEL || 'info';

const logger = pino({ level: LOG_LEVEL });
const sessions = new Map();

function ensureSessionDir(deviceId) {
  const baseDir = path.join(__dirname, 'sessions');
  const deviceDir = path.join(baseDir, String(deviceId));

  if (!fs.existsSync(baseDir)) {
    fs.mkdirSync(baseDir, { recursive: true });
  }

  if (!fs.existsSync(deviceDir)) {
    fs.mkdirSync(deviceDir, { recursive: true });
  }

  return deviceDir;
}

function parsePhone(jid) {
  if (!jid) {
    return null;
  }

  return jid.split(':')[0].split('@')[0];
}

async function sendDeviceWebhook(payload) {
  if (!WEBHOOK_TOKEN) {
    logger.warn('WHATSAPP_WEBHOOK_TOKEN is empty; device webhook skipped');
    return;
  }

  try {
    await axios.post(`${PANEL_BASE_URL}/api/webhooks/baileys/devices/status`, payload, {
      headers: {
        'X-Webhook-Token': WEBHOOK_TOKEN,
      },
    });
  } catch (error) {
    logger.error({ error: error.message, payload }, 'Device webhook failed');
  }
}

async function sendIncomingWebhook(payload) {
  if (!WEBHOOK_TOKEN) {
    logger.warn('WHATSAPP_WEBHOOK_TOKEN is empty; message webhook skipped');
    return;
  }

  try {
    await axios.post(`${PANEL_BASE_URL}/api/webhooks/baileys/messages`, payload, {
      headers: {
        'X-Webhook-Token': WEBHOOK_TOKEN,
      },
    });
  } catch (error) {
    logger.error({ error: error.message, payload }, 'Message webhook failed');
  }
}

async function startDeviceSession(deviceId, devicePhone, name) {
  if (sessions.has(deviceId)) {
    return sessions.get(deviceId);
  }

  const deviceDir = ensureSessionDir(deviceId);
  const { state, saveCreds } = await useMultiFileAuthState(deviceDir);
  const { version } = await fetchLatestBaileysVersion();

  const sock = makeWASocket({
    version,
    auth: state,
    logger: pino({ level: 'silent' }),
    printQRInTerminal: false,
  });

  sock.ev.on('creds.update', saveCreds);

  sock.ev.on('connection.update', async (update) => {
    const { connection, lastDisconnect, qr } = update;
    const now = new Date().toISOString();

    if (qr) {
      await sendDeviceWebhook({
        device_id: deviceId,
        phone_number: devicePhone || null,
        status: 'disconnected',
        session: { qr },
        last_seen_at: now,
      });
    }

    if (connection === 'open') {
      const phone = parsePhone(sock.user?.id) || devicePhone || null;

      await sendDeviceWebhook({
        device_id: deviceId,
        phone_number: phone,
        status: 'connected',
        session: { connected: true },
        last_connected_at: now,
        last_seen_at: now,
      });
    }

    if (connection === 'close') {
      const statusCode = lastDisconnect?.error?.output?.statusCode;
      const shouldReconnect = statusCode !== DisconnectReason.loggedOut;

      await sendDeviceWebhook({
        device_id: deviceId,
        phone_number: devicePhone || null,
        status: 'disconnected',
        last_seen_at: now,
      });

      if (shouldReconnect) {
        sessions.delete(deviceId);
        await startDeviceSession(deviceId, devicePhone, name);
      } else {
        sessions.delete(deviceId);
      }
    }
  });

  sock.ev.on('messages.upsert', async ({ messages, type }) => {
    if (type !== 'notify') {
      return;
    }

    for (const message of messages) {
      if (!message.message || message.key.fromMe) {
        continue;
      }

      const remoteJid = message.key.remoteJid || '';
      const from = parsePhone(remoteJid) || remoteJid;

      let messageType = 'text';
      let text = '';

      if (message.message.conversation) {
        text = message.message.conversation;
      } else if (message.message.extendedTextMessage?.text) {
        text = message.message.extendedTextMessage.text;
      } else if (message.message.imageMessage) {
        messageType = 'image';
        text = message.message.imageMessage.caption || '';
      } else if (message.message.documentMessage) {
        messageType = 'document';
        text = message.message.documentMessage.caption || '';
      }

      await sendIncomingWebhook({
        device_id: deviceId,
        device_phone: devicePhone || parsePhone(sock.user?.id) || null,
        from,
        type: messageType,
        message: text,
      });
    }
  });

  sessions.set(deviceId, sock);

  logger.info({ deviceId, name }, 'Device session started');

  return sock;
}

function removeSessionFiles(deviceId) {
  const deviceDir = path.join(__dirname, 'sessions', String(deviceId));
  if (!fs.existsSync(deviceDir)) {
    return;
  }
  fs.rmSync(deviceDir, { recursive: true, force: true });
}

function requireGatewayToken(req, res, next) {
  if (!GATEWAY_TOKEN) {
    return next();
  }

  const authHeader = req.headers.authorization || '';
  const token = authHeader.replace('Bearer ', '').trim();

  if (!token || token !== GATEWAY_TOKEN) {
    return res.status(401).json({ error: 'Unauthorized' });
  }

  return next();
}

app.get('/health', (_req, res) => {
  res.json({ status: 'ok' });
});

app.post('/devices/connect', requireGatewayToken, async (req, res) => {
  const { device_id: deviceId, device_phone: devicePhone, name } = req.body || {};

  if (!deviceId) {
    return res.status(422).json({ error: 'device_id is required' });
  }

  try {
    await startDeviceSession(deviceId, devicePhone, name);

    return res.json({ status: 'connecting', device_id: deviceId });
  } catch (error) {
    logger.error({ error: error.message }, 'Failed to connect device');

    return res.status(500).json({ error: 'Failed to connect device' });
  }
});

app.post('/devices/disconnect', requireGatewayToken, async (req, res) => {
  const { device_id: deviceId } = req.body || {};

  if (!deviceId) {
    return res.status(422).json({ error: 'device_id is required' });
  }

  const sock = sessions.get(deviceId);

  try {
    if (sock) {
      await sock.logout();
      sessions.delete(deviceId);
    }

    removeSessionFiles(deviceId);

    await sendDeviceWebhook({
      device_id: deviceId,
      status: 'disconnected',
      last_seen_at: new Date().toISOString(),
    });

    return res.json({ status: 'disconnected', device_id: deviceId });
  } catch (error) {
    logger.error({ error: error.message }, 'Failed to disconnect device');

    return res.status(500).json({ error: 'Failed to disconnect device' });
  }
});

app.post('/messages', requireGatewayToken, async (req, res) => {
  const { device_id: deviceId, to, type, message } = req.body || {};

  if (!deviceId || !to) {
    return res.status(422).json({ error: 'device_id and to are required' });
  }

  if (type && type !== 'text') {
    return res.status(422).json({ error: 'Only text messages are supported in minimal gateway.' });
  }

  const sock = sessions.get(deviceId);

  if (!sock) {
    return res.status(409).json({ error: 'Device session is not connected yet.' });
  }

  const normalized = to.replace(/\D/g, '');
  const jid = normalized.includes('@') ? normalized : `${normalized}@s.whatsapp.net`;

  try {
    await sock.sendMessage(jid, { text: message || '' });

    return res.json({ status: 'sent' });
  } catch (error) {
    logger.error({ error: error.message }, 'Failed to send message');

    return res.status(500).json({ error: 'Failed to send message' });
  }
});

app.listen(PORT, () => {
  logger.info({ port: PORT, panel: PANEL_BASE_URL }, 'Baileys gateway is running');
});
