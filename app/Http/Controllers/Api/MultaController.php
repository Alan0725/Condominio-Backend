<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Multa;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class MultaController extends Controller
{
    public function show(Request $request, Multa $multa)
    {
        abort_if(
            $multa->user_id !== $request->user()->id && ! $request->user()->is_admin,
            403
        );

        return response()->json($multa->load(['user:id,name,departamento', 'creator:id,name']));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'motivo' => ['required', 'string', 'max:255'],
            'monto' => ['required', 'numeric', 'min:0'],
        ]);

        $multa = Multa::create([
            ...$data,
            'created_by' => $request->user()->id,
        ]);

        $target = User::findOrFail($data['user_id']);

        Notification::send(
            $target,
            'multa',
            'Nueva multa registrada',
            "Se te registró una multa de \${$multa->monto} por: {$multa->motivo}",
            $multa,
        );

        return response()->json($multa, 201);
    }
}
