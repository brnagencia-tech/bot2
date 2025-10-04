<?php

namespace App\Http\Controllers\Mc;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $start = Carbon::parse($request->input('start', now()->subDays(7)))->startOfDay();
        $end = Carbon::parse($request->input('end', now()))->endOfDay();

        $sales = Message::where('direction','out')->whereBetween('created_at', [$start, $end])->count();
        $kpis = [
            'revenue' => 0, // placeholder
            'avg_ticket' => 0,
            'confirmed_sales' => $sales,
            'conversion_rate' => 0,
        ];

        // Funnel placeholders
        $funnel = [
            'new_clients' => Contact::whereBetween('created_at', [$start, $end])->count(),
            'reservations' => 0,
            'payments' => 0,
        ];

        // Charts placeholders
        $days = [];
        for ($i=0; $i<7; $i++) { $days[] = 0; }
        $hours = array_fill(0,24,0);

        // Sales by DDD (top 10)
        $ddd = Message::selectRaw("substr(replace(contacts.phone,'+',''),1,2) as ddd, count(*) as c")
            ->join('contacts','contacts.id','=','messages.contact_id')
            ->where('messages.direction','out')
            ->groupBy('ddd')->orderByDesc('c')->limit(10)->get();

        $features = [
            ['feature' => 'roletas', 'count' => 0],
            ['feature' => 'caixas', 'count' => 0],
        ];

        $topBuyers = [];

        $data = compact('kpis','funnel','days','hours','ddd','features','topBuyers');

        if (request()->wantsJson()) {
            return response()->json($data);
        }

        return view('mc.dashboard', ['data' => $data, 'start' => $start, 'end' => $end]);
    }
}

