<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedToken = config('services.whatsapp.webhook_token');

        if ($expectedToken && ! hash_equals($expectedToken, (string) $this->extractToken($request))) {
            abort(Response::HTTP_UNAUTHORIZED, 'Invalid webhook token.');
        }

        return $next($request);
    }

    private function extractToken(Request $request): ?string
    {
        return $request->header('X-Webhook-Token') ?? $request->header('X-Baileys-Token') ?? $request->input('token');
    }
}

