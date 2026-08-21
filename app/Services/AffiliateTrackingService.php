<?php

namespace App\Services;

use App\Click;
use App\Link;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AffiliateTrackingService
{
    public function __construct(private ReferralService $referralService)
    {
    }

    public function track(Link $link, Request $request): Click
    {
        $requestId = (string) ($request->header('X-Request-ID') ?: Str::uuid());
        $context = [
            'request_id' => $requestId,
            'link_id' => $link->id,
            'program_id' => $link->program_id,
            'user_id' => $link->user_id,
        ];

        if (!$link->isValid()) {
            throw new \DomainException('Link is not valid or has expired');
        }

        return DB::transaction(function () use ($link, $request, $context) {
            if ($request->filled('referral_code')) {
                $this->referralService->trackReferral($request->string('referral_code')->toString());
            }

            $userAgent = (string) ($request->userAgent() ?: $request->input('user_agent', 'Unknown'));
            $ipAddress = (string) ($request->ip() ?: $request->input('ip_address', '127.0.0.1'));
            $click = $link->clicks()->create([
                'user_id' => $link->user_id,
                'program_id' => $link->program_id,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'referrer' => $request->header('referer', $request->input('referrer')),
                'country' => $this->resolveCountry($ipAddress),
                'city' => $this->resolveCity($ipAddress),
                'device_type' => $this->getDeviceType($userAgent),
                'browser' => $this->getBrowser($userAgent),
                'os' => $this->getOs($userAgent),
                'clicked_at' => now(),
            ]);

            $link->increment('click_count');
            $click = $click->fresh(['link', 'user', 'program']);
            Log::info('Affiliate click tracked', $context + [
                'click_id' => $click->id,
                'device_type' => $click->device_type,
                'country' => $click->country,
            ]);

            return $click;
        });
    }

    private function resolveCountry(string $ip): ?string
    {
        // GeoIP is intentionally optional; never persist a fabricated location.
        return null;
    }

    private function resolveCity(string $ip): ?string
    {
        // GeoIP is intentionally optional; never persist a fabricated location.
        return null;
    }

    private function getDeviceType(string $userAgent): string
    {
        return preg_match('/Mobile|Android|iPhone|iPad/i', $userAgent) ? 'mobile' : 'desktop';
    }

    private function getBrowser(string $userAgent): string
    {
        foreach (['Edge', 'Chrome', 'Firefox', 'Safari'] as $browser) {
            if (preg_match('/' . preg_quote($browser, '/') . '/i', $userAgent)) {
                return $browser;
            }
        }
        return 'Unknown';
    }

    private function getOs(string $userAgent): string
    {
        foreach (['Windows', 'Mac', 'Linux', 'Android', 'iOS'] as $os) {
            if (preg_match('/' . preg_quote($os, '/') . '/i', $userAgent)) {
                return $os === 'Mac' ? 'macOS' : $os;
            }
        }
        return 'Unknown';
    }
}
