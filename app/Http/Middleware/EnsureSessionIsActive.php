<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureSessionIsActive
{
    /**
     * Reject requests from tokens that have been idle for too long.
     *
     * Must run BEFORE "auth:sanctum" (see the priority registration in
     * bootstrap/app.php): Sanctum's own guard stamps last_used_at with "now"
     * as soon as it authenticates the request, so a middleware placed after
     * it would always see the idle time as zero. Looking the token up
     * independently here lets us see its last activity before that happens.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $plainTextToken = $request->bearerToken();

        if ($plainTextToken) {
            $accessToken = PersonalAccessToken::findToken($plainTextToken);

            if ($accessToken && $accessToken->last_used_at) {
                $idleMinutes = abs(now()->diffInMinutes($accessToken->last_used_at));
                $timeout = (int) config('sanctum.idle_timeout_minutes', 120);

                if ($idleMinutes > $timeout) {
                    $accessToken->delete();

                    return response()->json([
                        'message' => 'Tu sesión ha expirado por inactividad. Inicia sesión de nuevo.',
                    ], 401);
                }
            }
        }

        return $next($request);
    }
}
