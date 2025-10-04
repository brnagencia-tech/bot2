<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Http;

class ChatController extends Controller
{
    public function index(Request $request): View
    {
        $contacts = Contact::orderBy('name')->get(['id','name','phone']);
        $contact = null;
        if ($request->filled('contact')) {
            $contact = Contact::with(['messages' => fn($q) => $q->orderBy('sent_at')])->find($request->integer('contact'));
        }
        return view('chat.index', compact('contacts','contact'));
    }

    public function messages(Request $request)
    {
        $request->validate(['contact' => ['required','integer']]);
        $contact = Contact::with(['messages' => fn($q) => $q->orderBy('sent_at')])->findOrFail($request->integer('contact'));
        return response()->json([
            'contact' => [
                'id' => $contact->id,
                'name' => $contact->name,
                'phone' => $contact->phone,
            ],
            'messages' => $contact->messages->map(fn($m) => [
                'id' => $m->id,
                'direction' => $m->direction,
                'body' => $m->body,
                'sent_at' => optional($m->sent_at)->toIso8601String(),
            ])->all(),
        ]);
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'contact' => ['required','integer','exists:contacts,id'],
            'text' => ['required','string','max:2000'],
        ]);

        $contact = Contact::findOrFail($data['contact']);
        $to = $contact->phone ?: preg_replace('/\D+/', '', (string) $contact->wa_id);
        if (!$to) {
            return response()->json(['ok' => false, 'error' => 'Contato sem telefone válido'], 422);
        }

        $base = rtrim((string) config('services.whatsapp_web.base_uri'), '/');
        try {
            $res = Http::timeout(10)->acceptJson()->post($base.'/send-message', [
                'to' => $to,
                'text' => $data['text'],
            ]);
            if (!$res->successful() || !($res->json()['ok'] ?? false)) {
                return response()->json(['ok' => false, 'error' => $res->json() ?? $res->body()], 502);
            }

            $msg = Message::create([
                'contact_id' => $contact->id,
                'direction' => 'out',
                'body' => $data['text'],
                'sent_at' => now(),
            ]);

            return response()->json(['ok' => true, 'id' => $msg->id]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
