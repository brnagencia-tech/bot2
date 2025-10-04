<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class WhatsAppController extends Controller
{
    // GET /webhook/whatsapp (verification)
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode') ?? $request->query('hub.mode');
        $token = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');
        $challenge = $request->query('hub_challenge') ?? $request->query('hub.challenge');

        if ($mode === 'subscribe' && $token && $challenge) {
            if ($token === config('services.whatsapp.verify_token')) {
                return response($challenge, 200);
            }
            return response('Forbidden', 403);
        }

        return response('Bad Request', 400);
    }

    // POST /webhook/whatsapp (receive messages)
    public function receive(Request $request)
    {
        // Optional: verify signature if configured
        if (config('services.whatsapp.enforce_signature')) {
            $sig = $request->header('X-Hub-Signature-256');
            $secret = config('services.whatsapp.app_secret');
            if (!$sig || !$secret || !$this->verifySignature($sig, $request->getContent(), $secret)) {
                return response()->json(['error' => 'invalid_signature'], 401);
            }
        }

        $payload = $request->all();
        $value = $payload['entry'][0]['changes'][0]['value'] ?? [];
        $messages = $value['messages'] ?? [];
        if (empty($messages)) {
            return response()->json(['status' => 'ignored'], 200);
        }

        $message = $messages[0];
        $from = $message['from'] ?? null; // WaId (phone)
        $text = $message['text']['body'] ?? '';
        $type = $message['type'] ?? 'text';

        if (!$from) {
            return response()->json(['status' => 'ignored'], 200);
        }

        $reply = 'Olá!';
        try {
            $aiBase = config('services.ai.base_uri');
            $aiRes = Http::timeout(10)->post(rtrim($aiBase, '/').'/generate', [
                'messages' => [
                    ['role' => 'system', 'content' => 'Você é um assistente do BotWhatsApp. Responda em português.'],
                    ['role' => 'user', 'content' => $text],
                ],
            ]);
            if ($aiRes->successful()) {
                $reply = (string) data_get($aiRes->json(), 'reply', $reply);
            } else {
                Log::warning('AI service error', ['status' => $aiRes->status(), 'body' => $aiRes->body()]);
            }
        } catch (\Throwable $e) {
            Log::warning('AI service exception: '.$e->getMessage());
        }

        $sent = $this->sendWhatsAppText($from, $reply, data_get($value, 'metadata.phone_number_id'));
        if (!$sent) {
            return response()->json(['status' => 'failed_to_send'], 502);
        }

        return response()->json(['status' => 'ok']);
    }

    private function sendWhatsAppText(string $to, string $body, ?string $fallbackPhoneNumberId = null): bool
    {
        $token = config('services.whatsapp.token');
        $phoneNumberId = config('services.whatsapp.phone_number_id') ?: $fallbackPhoneNumberId;
        $version = trim((string) config('services.whatsapp.graph_version', 'v20.0'));

        if (!$token || !$phoneNumberId) {
            Log::error('WhatsApp token or phone_number_id missing');
            return false;
        }

        $endpoint = sprintf('https://graph.facebook.com/%s/%s/messages', $version, $phoneNumberId);
        try {
            $res = Http::timeout(10)
                ->withToken($token)
                ->acceptJson()
                ->post($endpoint, [
                    'messaging_product' => 'whatsapp',
                    'to' => $to,
                    'type' => 'text',
                    'text' => ['body' => $body],
                ]);
            if (!$res->successful()) {
                Log::error('WhatsApp send error', ['status' => $res->status(), 'body' => $res->body()]);
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            Log::error('WhatsApp send exception: '.$e->getMessage());
            return false;
        }
    }

    private function verifySignature(string $headerSig, string $payload, string $appSecret): bool
    {
        // Header format: sha256=hexdigest
        if (!str_starts_with($headerSig, 'sha256=')) {
            return false;
        }
        $sig = substr($headerSig, 7);
        $expected = hash_hmac('sha256', $payload, $appSecret);
        return hash_equals($expected, $sig);
    }
}

