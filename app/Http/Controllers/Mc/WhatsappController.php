<?php

namespace App\Http\Controllers\Mc;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Cache;

class WhatsappController extends Controller
{
    public function status(WhatsAppService $wa)
    {
        $data = Cache::remember('wa.status', 3, fn() => $wa->status());
        return response()->json($data);
    }

    public function qr(WhatsAppService $wa)
    {
        $bin = Cache::remember('wa.qr', 3, fn() => $wa->qrPngBinary());
        if ($bin === null) {
            return response()->noContent();
        }
        return response($bin, 200, ['Content-Type' => 'image/png']);
    }
}

