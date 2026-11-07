<?php

namespace App\Http\Middleware;

use App\Support\Analytics;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records storefront page views. The visitor cookie is set on the response;
 * the database write happens in terminate(), after the page has been sent.
 */
class TrackPageView
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->isTrackable($request, $response)) {
            $request->attributes->set('track_page_view', true);

            if (! $request->cookie(Analytics::COOKIE)) {
                $response->headers->setCookie(cookie(
                    Analytics::COOKIE, Analytics::visitorId($request), 60 * 24 * 365, '/', null, $request->isSecure(), true, false, 'lax'
                ));
            }
        }

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        if ($request->attributes->get('track_page_view')) {
            Analytics::record($request, 'page_view', ['path' => '/' . ltrim($request->path(), '/')]);
        }
    }

    private function isTrackable(Request $request, Response $response): bool
    {
        return $request->isMethod('GET')
            && ! $request->expectsJson()
            && $response->getStatusCode() === 200
            && str_contains((string) $response->headers->get('Content-Type'), 'text/html')
            && ! $request->is('admin', 'admin/*', 'login', 'register', 'track', 'up')
            && Analytics::shouldTrack($request);
    }
}
