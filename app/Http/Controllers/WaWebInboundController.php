<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Message;
use Illuminate\Http\Request;

class WaWebInboundController extends Controller
{
    public function inbound(Request $request)
    {
        $secret = config('services.whatsapp_web.shared_secret');
        $header = $request->header('X-WA-SECRET');
        if ($secret && $header !== $secret) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        $data = $request->validate([
            'wa_id' => ['required','string','max:100'],
            'phone' => ['nullable','string','max:32'],
            'name' => ['nullable','string','max:255'],
            'body' => ['required','string'],
            'sent_at' => ['nullable','date'],
        ]);

        $contact = Contact::firstOrCreate(
            ['wa_id' => $data['wa_id']],
            ['name' => $data['name'] ?? null, 'phone' => $data['phone'] ?? null]
        );
        if (!$contact->name && !empty($data['name'])) { $contact->name = $data['name']; }
        if (!$contact->phone && !empty($data['phone'])) { $contact->phone = $data['phone']; }
        $contact->save();

        Message::create([
            'contact_id' => $contact->id,
            'direction' => 'in',
            'body' => $data['body'],
            'sent_at' => $data['sent_at'] ?? now(),
        ]);

        return response()->json(['ok' => true]);
    }
}

