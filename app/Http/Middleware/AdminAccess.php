<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs on every admin page (after "auth" and "admin"):
 *  1. Admins with a temporary password must change it before doing anything else.
 *  2. Each admin may only open the areas a super admin gave them (route name admin.{area}.*).
 *     Areas not listed in User::ADMIN_SECTIONS (e.g. "team") are super-admin only.
 */
class AdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $route = (string) $request->route()?->getName();

        if ($user->must_change_password && ! str_starts_with($route, 'admin.password.')) {
            return redirect()->route('admin.password.edit')
                ->with('error', 'Please choose your own password before continuing.');
        }

        $section = $this->section($route);
        if ($section === null || $user->canAccessAdmin($section)) {
            return $next($request);
        }

        $home = $user->adminHomeRoute();
        if ($home && $home !== $route) {
            // Signing in lands on the dashboard; quietly use their first allowed page instead
            return $section === 'dashboard'
                ? redirect()->route($home)
                : redirect()->route($home)->with('error', 'You don\'t have access to that page. Ask a super admin if you need it.');
        }

        abort(403, 'Your admin account has not been given access to any pages yet. Ask a super admin.');
    }

    /**
     * admin.orders.show -> "orders"; admin.dashboard -> "dashboard"; admin.password.* -> always allowed.
     */
    private function section(string $route): ?string
    {
        $area = explode('.', $route)[1] ?? '';

        return match (true) {
            $area === '' || $area === 'password' => null,
            default => $area,
        };
    }
}
