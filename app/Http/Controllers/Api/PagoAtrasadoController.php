<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\PagoAtrasado;
use App\Models\User;
use Illuminate\Http\Request;

class PagoAtrasadoController extends Controller
{
    public function show(Request $request, PagoAtrasado $pagoAtrasado)
    {
        abort_if(
            $pagoAtrasado->user_id !== $request->user()->id && ! $request->user()->is_admin,
            403
        );

        return response()->json($pagoAtrasado->load(['user:id,name,departamento', 'creator:id,name']));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'monto' => ['required', 'numeric', 'min:0'],
            'periodo' => ['required', 'string', 'max:255'],
        ]);

        $pago = PagoAtrasado::create([
            ...$data,
            'created_by' => $request->user()->id,
        ]);

        $target = User::findOrFail($data['user_id']);

        Notification::send(
            $target,
            'pago_atrasado',
            'Pago atrasado registrado',
            "Tienes un pago atrasado de \${$pago->monto} correspondiente a {$pago->periodo}",
            $pago,
        );

        return response()->json($pago, 201);
    }
}
