<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class SanctumTokenRenewal
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @spec-link [[mech_sanctum_token_renewal]]
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Authenticate via Sanctum if not already
        if (! Auth::guard('sanctum')->check()) {
            return $next($request);
        }

        $user = Auth::guard('sanctum')->user();
        $token = $user->currentAccessToken();
        
        // Skip for TransientToken (used by actingAs correctly in tests)
        if ($token instanceof \Laravel\Sanctum\TransientToken) {
            return $next($request);
        }

        if ($token) {
            $createdAt = $token->created_at;
            $now = now();
            $ageInMinutes = $createdAt->diffInMinutes($now);

            // 2. Check for renewal trigger (10-15 minutes)
            if ($ageInMinutes >= 10 && $ageInMinutes < 15) {
                
                // 3. Prevent double-renewal during grace period
                // If expires_at is in the very near future (grace period), skip
                $isGracePeriod = $token->expires_at && $token->expires_at->isFuture() && $now->diffInSeconds($token->expires_at) <= 20;

                if (!$isGracePeriod) {
                    // Issue new token
                    $newTokenResult = $user->createToken($token->name, expiresAt: now()->addMinutes(15));
                    
                    // Set grace period on current token
                    $token->forceFill([
                        'expires_at' => now()->addSeconds(20),
                    ])->save();

                    // Store new token in request for the post-response phase
                    $request->attributes->set('sanctum_renewed_token', $newTokenResult->plainTextToken);
                }
            }
        }

        $response = $next($request);

        // 4. Post-response: Inject new token into metadata if generated
        if ($request->attributes->has('sanctum_renewed_token')) {
            $newToken = $request->attributes->get('sanctum_renewed_token');
            
            if ($response instanceof \Illuminate\Http\JsonResponse) {
                $content = $response->getData(true);
                
                if (isset($content['meta'])) {
                    $content['meta']['token'] = $newToken;
                    $content['meta']['message'] = 'Token renewed';
                    $response->setData($content);
                }
            }
        }

        return $response;
    }
}
