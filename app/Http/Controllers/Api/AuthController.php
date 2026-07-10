<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\ResetPasswordCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'departamento' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'departamento' => $data['departamento'],
            'password' => Hash::make($data['password']),
        ]);

        $user->sendEmailVerificationNotification();

        return response()->json([
            'user' => $user,
            'token' => $user->createToken($this->deviceName($request))->plainTextToken,
        ], 201);
    }

    public function resendVerification(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Tu correo ya está confirmado.']);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Te enviamos un nuevo correo de confirmación.']);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales no coinciden con nuestros registros.'],
            ]);
        }

        return response()->json([
            'user' => $user,
            'token' => $user->createToken($this->deviceName($request))->plainTextToken,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada.']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['La contraseña actual no es correcta.'],
            ]);
        }

        $user->update(['password' => Hash::make($data['password'])]);

        $user->tokens()->delete();

        return response()->json([
            'message' => 'Contraseña actualizada. Se cerró la sesión en todos los dispositivos.',
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if ($user) {
            $code = (string) random_int(100000, 999999);

            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            DB::table('password_reset_tokens')->insert([
                'email' => $user->email,
                'token' => Hash::make($code),
                'created_at' => now(),
            ]);

            $user->notify(new ResetPasswordCode($code));
        }

        return response()->json([
            'message' => 'Si el correo existe en nuestros registros, te enviamos un código de recuperación.',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email'],
            'code' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $data['email'])->first();

        $isValid = $record
            && Hash::check($data['code'], $record->token)
            && now()->diffInMinutes($record->created_at) <= 15;

        if (! $isValid) {
            throw ValidationException::withMessages([
                'code' => ['El código es inválido o ha expirado. Solicita uno nuevo.'],
            ]);
        }

        $user = User::where('email', $data['email'])->first();

        abort_if(! $user, 404);

        $user->update(['password' => Hash::make($data['password'])]);
        $user->tokens()->delete();

        DB::table('password_reset_tokens')->where('email', $data['email'])->delete();

        return response()->json([
            'message' => 'Contraseña actualizada correctamente. Ya puedes iniciar sesión.',
        ]);
    }

    /**
     * Derive a human-readable device label from the request's user agent,
     * so each device/browser gets its own named Sanctum token.
     */
    private function deviceName(Request $request): string
    {
        return substr($request->userAgent() ?? 'dispositivo desconocido', 0, 255);
    }
}
