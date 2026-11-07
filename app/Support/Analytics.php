<?php

namespace App\Support;

use App\Models\AnalyticsEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Lightweight, anonymous storefront tracking.
 * Visitors get a random ID in a first-party cookie; no names, emails or IPs are stored.
 * Admin visits and obvious bots are ignored.
 */
class Analytics
{
    public const COOKIE = 'nv_id';

    private const BOTS = '/bot|crawl|spider|slurp|facebookexternalhit|preview|headless|lighthouse|pingdom|monitor|curl|wget|python-requests|httpclient|go-http/i';

    public static function shouldTrack(Request $request): bool
    {
        if (auth()->check() && auth()->user()->isAdmin()) {
            return false;
        }

        $agent = (string) $request->userAgent();

        return $agent !== '' && ! preg_match(self::BOTS, $agent);
    }

    /**
     * The visitor's anonymous ID (an existing cookie, or a new one this request will set).
     */
    public static function visitorId(Request $request): string
    {
        $id = $request->cookie(self::COOKIE);
        if (is_string($id) && Str::isUuid($id)) {
            return $id;
        }

        return $request->attributes->get(self::COOKIE) ?? tap((string) Str::uuid(), function ($new) use ($request) {
            $request->attributes->set(self::COOKIE, $new);
        });
    }

    public static function record(Request $request, string $type, array $data = []): void
    {
        if (! in_array($type, AnalyticsEvent::TYPES, true) || ! self::shouldTrack($request)) {
            return;
        }

        try {
            AnalyticsEvent::create([
                'type' => $type,
                'visitor_id' => self::visitorId($request),
                'path' => isset($data['path']) ? mb_substr($data['path'], 0, 255) : null,
                'product_id' => $data['product_id'] ?? null,
                'variant_id' => $data['variant_id'] ?? null,
                'source' => $data['source'] ?? null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Tracking must never break the storefront
            Log::warning('Analytics event not recorded: ' . $e->getMessage());
        }
    }
}
