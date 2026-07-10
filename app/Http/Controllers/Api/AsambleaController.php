<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asamblea;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class AsambleaController extends Controller
{
    public function index()
    {
        return response()->json(
            Asamblea::with('creator:id,name')->latest('fecha')->get()
        );
    }

    public function show(Asamblea $asamblea)
    {
        return response()->json($asamblea->load('creator:id,name'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['required', 'string'],
            'fecha' => ['required', 'date'],
        ]);

        $asamblea = Asamblea::create([
            ...$data,
            'created_by' => $request->user()->id,
        ]);

        $fechaFormateada = $asamblea->fecha->format('d/m/Y H:i');

        User::query()->each(function (User $user) use ($asamblea, $fechaFormateada) {
            Notification::send(
                $user,
                'asamblea',
                "Nueva asamblea: {$asamblea->titulo}",
                "Programada para el {$fechaFormateada}",
                $asamblea,
            );
        });

        return response()->json($asamblea, 201);
    }
}
