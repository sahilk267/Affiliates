<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyPartnerSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = (string) config('services.affiliate_api.key');
        $secret = (string) config('services.affiliate_api.secret');
        $timestamp = (string) $request->header('X-Affiliate-Timestamp');
        $signature = (string) $request->header('X-Affiliate-Signature');
        $providedKey = (string) $request->header('X-Affiliate-Key');

        if ($key === '' || $secret === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'Partner API is not configured',
            ], 503);
        }

        if ($providedKey === '' || !hash_equals($key, $providedKey)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid partner credentials',
            ], 401);
        }

        if ($timestamp === '' || !ctype_digit($timestamp) || abs(time() - (int) $timestamp) > 300) {
            return response()->json([
                'status' => 'error',
                'message' => 'Expired or invalid partner timestamp',
            ], 401);
        }

        $payload = $timestamp . '.' . $request->getContent();
        $expected = hash_hmac('sha256', $payload, $secret);
        if ($signature === '' || !hash_equals($expected, $signature)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid partner signature',
            ], 401);
        }

        $request->attributes->set('partner_api_key', $providedKey);
        return $next($request);
    }
}
