<?php

namespace App\Http\Controllers\Mc;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;

class ClientsController extends Controller
{
    public function index(Request $request)
    {
        $q = Contact::query();
        if ($ddd = $request->string('ddd')->toString()) {
            $q->where('phone', 'like', $ddd.'%');
        }
        $contacts = $q->orderByDesc('id')->paginate(20);
        return view('mc.clients.index', compact('contacts'));
    }
}

