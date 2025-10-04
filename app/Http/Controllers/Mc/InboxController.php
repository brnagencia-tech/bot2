<?php

namespace App\Http\Controllers\Mc;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Message;
use App\Services\OpenAiChatService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class InboxController extends Controller
{
    public function index(Request $request, WhatsAppService $wa)
    {
        $contacts = Contact::query()->orderByDesc('id')->limit(30)->get();
        $status = $wa->status();
        return view('mc.inbox', [
            'contacts' => $contacts,
            'waStatus' => $status,
        ]);
    }

    public function send(Request $request, WhatsAppService $wa, OpenAiChatService $ai)
    {
        $data = $request->validate([
            'to' => ['required','string'],
            'text' => ['required','string'],
        ]);

        $to = preg_replace('/\D+/', '', $data['to']);
        $text = trim($data['text']);

        $ok = $wa->send($to, $text);

        // Persist message
        $waId = $to . '@s.whatsapp.net';
        $contact = Contact::firstOrCreate(['wa_id' => $waId], [
            'name' => null,
            'phone' => $to,
        ]);
        Message::create([
            'contact_id' => $contact->id,
            'direction' => 'out',
            'body' => $text,
            'sent_at' => now(),
        ]);

        return response()->json(['ok' => (bool)$ok]);
    }
}

