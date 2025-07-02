<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Http\Request;
use App\Events\MessageSent;

class ChatController extends Controller
{

    public function startChat()
    {
        $chat = Chat::firstOrCreate([
            'user_id' => auth()->id(),
            'admin_id' => null,
        ]);

        return response()->json($chat, 201);
    }


    public function listChats()
{

    $chats = Chat::with(['user', 'messages' => function ($query) {
        $query->latest()->take(1);
    }])->get();


    $chatsData = $chats->map(function ($chat) {
        return [
            'id' => $chat->id,
            'participant' => $chat->user ? $chat->user->name : 'Unknown User',
            'latest_message' => $chat->messages->isNotEmpty() ? $chat->messages->first()->content : 'No messages yet',
        ];
    });

    return response()->json($chatsData);
}



    public function fetchMessages($chatId)
    {
        $chat = Chat::findOrFail($chatId);

        if (auth()->id() !== $chat->user_id && auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($chat->messages()->with('sender')->get());
    }


    public function sendMessage(Request $request, $chatId)
    {

        $request->validate([
            'content' => 'required|string|max:500',
        ]);


        $chat = Chat::findOrFail($chatId);


        $message = $chat->messages()->create([
            'sender_id' => auth()->user()->id,
            'content' => $request->content,
        ]);


        broadcast(new MessageSent($chat, $message));


        return response()->json(['message' => $message], 201);
    }
}
