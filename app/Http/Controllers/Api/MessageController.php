<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index()
    {
        $messages = Message::with('user:id,name,departamento')
            ->latest()
            ->take(50)
            ->get()
            ->reverse()
            ->values();

        return response()->json($messages);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
        ]);

        $message = $request->user()->messages()->create([
            'body' => $data['body'],
        ]);

        $message->load('user:id,name,departamento');

        broadcast(new MessageSent($message));

        User::where('id', '!=', $request->user()->id)->each(function (User $user) use ($message) {
            Notification::send(
                $user,
                'mensaje',
                "Nuevo mensaje de {$message->user->name}",
                str($message->body)->limit(80)->toString(),
                $message,
            );
        });

        return response()->json($message, 201);
    }
}
