<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Message;
use Illuminate\Database\Seeder;

class McDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Contact::count() >= 10) return;
        $ddds = ['11','21','31','41','51','61','71','81','85','98'];
        foreach (range(1,10) as $i) {
            $ddd = $ddds[array_rand($ddds)];
            $num = $ddd . str_pad((string)random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
            $c = Contact::create([
                'wa_id' => $num.'@s.whatsapp.net',
                'name' => 'Contato '.$i,
                'phone' => $num,
            ]);
            foreach (range(1,5) as $j) {
                Message::create([
                    'contact_id' => $c->id,
                    'direction' => $j % 2 ? 'in' : 'out',
                    'body' => 'Mensagem '.$j.' do contato '.$i,
                    'sent_at' => now()->subDays(random_int(0,10)),
                ]);
            }
        }
    }
}

