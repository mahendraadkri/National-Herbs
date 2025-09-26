<?php

namespace App\Http\Controllers;

use App\Models\ContactUs;
use App\Services\MailService;
use Illuminate\Http\Request;


class ContactUsController extends Controller
{
    public function index()
    {
        $contact = ContactUs::all();
        return response()->json(['categories' => $contact], 200);
        
    }

     public function store(Request $request, MailService $mailService)
    {
        $request->validate([
            'name'    => 'required|string',
            'email'   => 'required|email',
            'phone'   => ['nullable','regex:/^(97|98)[0-9]{8}$/'],
            'message' => 'required|string',
        ]);

        $data = $request->only(['name','email','phone','message']);
        $contact = ContactUs::create($data);

        $mailService->sendContactMail($data); 

        return response()->json([
            'message' => 'Your message has been received successfully!',
            'contact' => $contact
        ], 201);
    }

    public function destroy($id)
    {
        $contact = ContactUs::find($id);
        
        if (!$contact) {
            return response()->json(['message' => 'Contact message not found'], 404);
        }
        
        $contact->delete();
        
        return response()->json(['message' => 'Contact message deleted successfully'], 200);
    }



}
