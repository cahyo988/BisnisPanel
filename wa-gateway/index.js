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

function getMetadataPath(deviceId) {
  const deviceDir = ensureSessionDir(deviceId);

  return path.join(deviceDir, 'metadata.json');
}

function readDeviceMetadata(deviceId) {
  const filePath = path.join(__dirname, 'sessions', String(deviceId), 'metadata.json');

  if (!fs.existsSync(filePath)) {
    return null;
  }

  try {
    const raw = fs.readFileSync(filePath, 'utf-8');

    return JSON.parse(raw);
  } catch (error) {
    logger.warn({ deviceId, error: error.message }, 'Failed to parse device metadata');

    return null;
  }
}

function saveDeviceMetadata(deviceId, metadata) {
  try {
    fs.writeFileSync(
      getMetadataPath(deviceId),
      JSON.stringify(
        {
          device_id: deviceId,
          updated_at: new Date().toISOString(),
          ...metadata,
        },
        null,
        2
      )
    );
  } catch (error) {
    logger.warn({ deviceId, error: error.message }, 'Failed to persist device metadata');
  }
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

  saveDeviceMetadata(deviceId, {
    device_phone: devicePhone || null,
    name: name || null,
  });

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

    logger.info({ deviceId, updateKeys: Object.keys(update) }, 'Connection update received');

    if (qr) {
      logger.info({ deviceId, qrLength: qr.length }, 'QR code generated, sending to webhook');
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
      logger.info({ deviceId, phone }, 'Connection opened successfully');

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

      logger.info({ deviceId, statusCode, shouldReconnect }, 'Connection closed');

      await sendDeviceWebhook({
        device_id: deviceId,
        phone_number: devicePhone || null,
        status: 'disconnected',
        last_seen_at: now,
      });

      if (shouldReconnect) {
        sessions.delete(deviceId);
        logger.info({ deviceId }, 'Attempting to reconnect');
        await startDeviceSession(deviceId, devicePhone, name);
      } else {
        sessions.delete(deviceId);
        logger.info({ deviceId }, 'Not reconnecting (logged out)');
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

async function bootstrapExistingSessions() {
  const baseDir = path.join(__dirname, 'sessions');

  if (!fs.existsSync(baseDir)) {
    logger.info('No previous sessions to restore');
    return;
  }

  const entries = fs.readdirSync(baseDir, { withFileTypes: true });

  for (const entry of entries) {
    if (!entry.isDirectory()) {
      continue;
    }

    const deviceId = Number(entry.name);

    if (Number.isNaN(deviceId)) {
      logger.warn({ entry: entry.name }, 'Skipping invalid session directory name');
      continue;
    }

    const metadata = readDeviceMetadata(deviceId) || {};
    const devicePhone = metadata.device_phone || null;
    const name = metadata.name || null;

    try {
      await startDeviceSession(deviceId, devicePhone, name);
      logger.info({ deviceId }, 'Restored device session from disk');
    } catch (error) {
      logger.error({ deviceId, error: error.message }, 'Failed to restore device session');
    }
  }
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
  const { device_id: deviceId, device_phone: devicePhone, name, force } = req.body || {};

  if (!deviceId) {
    return res.status(422).json({ error: 'device_id is required' });
  }

  try {
    if (force) {
      logger.info({ deviceId }, 'Force cleanup requested');

      const sock = sessions.get(deviceId);
      if (sock) {
        logger.info({ deviceId }, 'Existing session found, cleaning up');

        // Stop listening to events
        sock.ev.removeAllListeners('connection.update');
        sock.ev.removeAllListeners('creds.update');
        sock.ev.removeAllListeners('messages.upsert');

        // Try to logout properly
        try {
          await sock.logout();
          logger.info({ deviceId }, 'Logged out successfully');
        } catch (err) {
          logger.warn({ deviceId, error: err.message }, 'Logout failed, continuing cleanup');
        }

        // Wait longer for logout to propagate to WhatsApp servers
        await new Promise((resolve) => setTimeout(resolve, 2000));

        // End connection
        try {
          sock.end(undefined);
        } catch (err) {
          logger.warn({ deviceId, error: err.message }, 'End connection failed');
        }

        sessions.delete(deviceId);

        // Verify session is deleted
        if (sessions.has(deviceId)) {
          logger.error({ deviceId }, 'Failed to delete session from memory');
        } else {
          logger.info({ deviceId }, 'Session removed from memory');
        }
      }

      // Remove session files
      try {
        removeSessionFiles(deviceId);
        logger.info({ deviceId }, 'Session files removed');
      } catch (err) {
        logger.warn({ deviceId, error: err.message }, 'Session file removal failed');
      }

      // Extended delay to ensure WhatsApp servers fully recognize disconnect
      logger.info({ deviceId }, 'Waiting for WhatsApp to fully disconnect...');
      await new Promise((resolve) => setTimeout(resolve, 3000));
    }

    logger.info({ deviceId }, 'Starting new device session');
    await startDeviceSession(deviceId, devicePhone, name);

    return res.json({ status: 'connecting', device_id: deviceId });
  } catch (error) {
    logger.error({ error: error.message, stack: error.stack }, 'Failed to connect device');

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
      // Stop listening to events to prevent side effects during cleanup
      sock.ev.removeAllListeners('connection.update');
      sock.ev.removeAllListeners('creds.update');

      try {
        await sock.logout();
      } catch (err) {
        // Ignore logout errors (e.g. if already disconnected)
      }

      sock.end(undefined);
      sessions.delete(deviceId);
    }

    // Small delay to ensure file locks are released on Windows
    await new Promise((resolve) => setTimeout(resolve, 500));

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

bootstrapExistingSessions()
  .then(() => {
    logger.info('Finished restoring existing sessions');
  })
  .catch((error) => {
    logger.error({ error: error.message }, 'Failed to restore sessions on startup');
  });
