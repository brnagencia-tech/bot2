<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(): View
    {
        $contacts = Contact::orderByDesc('updated_at')->paginate(20);
        return view('contacts.index', compact('contacts'));
    }
}

