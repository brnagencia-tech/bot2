<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AIController extends Controller
{
    public function generate(Request $request)
    {
        $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $base = config('services.ai.base_uri');

        try {
            $response = Http::timeout(10)->post(rtrim($base, '/').'/generate', [
                'messages' => [
                    ['role' => 'system', 'content' => 'Você é um assistente do BotWhatsApp. Responda em português.'],
                    ['role' => 'user', 'content' => $request->string('message')],
                ],
            ]);

            if (!$response->successful()) {
                return response()->json([
                    'error' => 'AI service error',
                    'status' => $response->status(),
                    'body' => $response->json() ?? $response->body(),
                ], 502);
            }

            $data = $response->json();
            return response()->json([
                'reply' => $data['reply'] ?? '',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'AI service unavailable',
                'message' => $e->getMessage(),
            ], 503);
        }
    }
}

