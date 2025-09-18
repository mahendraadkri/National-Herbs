<?php

namespace App\Http\Controllers;

use App\Models\ContactUs;
use Illuminate\Http\Request;

class ContactUsController extends Controller
{
    public function index()
    {
        $contact = ContactUs::all();
        return response()->json(['categories' => $contact], 200);
        
    }

     public function store(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email',
            'phone'   => ['regex:/^(97|98)[0-9]{8}$/'],
            'message' => 'required|string',
        ]);

        ContactUs::create($request->all());

        return response()->json(['message' => 'Your message has been received!'], 201);
    }
}
