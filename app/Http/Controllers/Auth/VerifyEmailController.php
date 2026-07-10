<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;

class VerifyEmailController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(int $id, string $hash)
    {
        $user = User::findOrFail($id);

        abort_unless(
            hash_equals(sha1($user->getEmailForVerification()), $hash),
            403,
            'Enlace de verificación inválido.'
        );

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return redirect(rtrim(config('app.frontend_url'), '/').'/email-verificado');
    }
}
