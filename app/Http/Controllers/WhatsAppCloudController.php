<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppCloudController extends Controller
{
    public function index()
    {
        $config = [
            'phone_number_id' => config('services.whatsapp.phone_number_id'),
            'graph_version' => config('services.whatsapp.graph_version', 'v20.0'),
            'verify_token' => config('services.whatsapp.verify_token'),
            'webhook_url' => url('/webhook/whatsapp'),
            'token_present' => (bool) config('services.whatsapp.token'),
        ];

        return view('whatsapp/cloud', compact('config'));
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'to' => ['required', 'string', 'max:20'],
            'text' => ['required', 'string', 'max:1000'],
        ]);

        $token = config('services.whatsapp.token');
        $phoneNumberId = config('services.whatsapp.phone_number_id');
        $version = trim((string) config('services.whatsapp.graph_version', 'v20.0'));

        if (!$token || !$phoneNumberId) {
            return response()->json([
                'ok' => false,
                'error' => 'Configuração incompleta: defina WHATSAPP_TOKEN e WHATSAPP_PHONE_NUMBER_ID',
            ], 422);
        }

        $endpoint = sprintf('https://graph.facebook.com/%s/%s/messages', $version, $phoneNumberId);

        try {
            $res = Http::timeout(15)
                ->withToken($token)
                ->acceptJson()
                ->post($endpoint, [
                    'messaging_product' => 'whatsapp',
                    'to' => preg_replace('/\D+/', '', $data['to']),
                    'type' => 'text',
                    'text' => [ 'body' => $data['text'] ],
                ]);

            if (!$res->successful()) {
                Log::error('WhatsApp Cloud send error', ['status' => $res->status(), 'body' => $res->body()]);
                return response()->json([
                    'ok' => false,
                    'status' => $res->status(),
                    'error' => $res->json() ?? $res->body(),
                ], 502);
            }

            $body = $res->json();
            return response()->json(['ok' => true, 'response' => $body]);
        } catch (\Throwable $e) {
            Log::error('WhatsApp Cloud send exception: '.$e->getMessage());
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

