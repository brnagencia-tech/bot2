<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WhatsAppWebController extends Controller
{
    public function index()
    {
        return view('whatsapp.index');
    }

    public function status()
    {
        $base = rtrim(config('services.whatsapp_web.base_uri'), '/');
        $res = Http::timeout(8)->get($base.'/status');
        if (!$res->successful()) {
            return response()->json(['error' => 'service_unavailable', 'status' => $res->status()], 502);
        }
        return response()->json($res->json());
    }

    public function qr()
    {
        $base = rtrim(config('services.whatsapp_web.base_uri'), '/');
        $res = Http::timeout(8)->get($base.'/qr');
        if ($res->status() === 204) {
            return response()->noContent();
        }
        if (!$res->successful()) {
            return response()->json(['error' => 'service_unavailable', 'status' => $res->status()], 502);
        }
        return response()->json($res->json());
    }

    public function logout()
    {
        $base = rtrim(config('services.whatsapp_web.base_uri'), '/');
        $res = Http::timeout(8)->post($base.'/logout');
        if (!$res->successful()) {
            return response()->json(['error' => 'service_unavailable', 'status' => $res->status()], 502);
        }
        return response()->json($res->json());
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'to' => ['required', 'string', 'max:32'],
            'text' => ['required', 'string', 'max:1000'],
        ]);

        $base = rtrim(config('services.whatsapp_web.base_uri'), '/');
        $res = Http::timeout(12)
            ->acceptJson()
            ->post($base.'/send-message', $data);
        if (!$res->successful()) {
            return response()->json(['ok' => false, 'status' => $res->status(), 'error' => $res->body()], 502);
        }
        return response()->json($res->json());
    }

    public function reset()
    {
        $base = rtrim(config('services.whatsapp_web.base_uri'), '/');
        $res = Http::timeout(12)->post($base.'/reset');
        if (!$res->successful()) {
            return response()->json(['error' => 'service_unavailable', 'status' => $res->status()], 502);
        }
        return response()->json($res->json());
    }
}
