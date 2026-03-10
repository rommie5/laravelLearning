<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index()
    {
        return Message::orderBy('created_at', 'asc')->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'role' => 'required|in:user,assistant',
            'content' => 'required|string',
        ]);

        return Message::create($request->only(['role', 'content']));
    }

    public function destroy()
    {
        Message::truncate();
        return response()->json(['status' => 'cleared']);
    }

    public function chat(Request $request)
    {
        // Mock AI response for the UI testing 
        $userPrompt = $request->input('prompt');
        $reply = "I understand you said: '{$userPrompt}'. This is a mock response from the backend. Since I am an AI, I suggest we verify this works nicely.";
        
        return response()->json(['reply' => $reply]);
    }
}
