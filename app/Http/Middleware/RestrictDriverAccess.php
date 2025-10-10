<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RestrictDriverAccess
{
    /**
     * List of route names drivers are allowed to access.
     */
    private array $allowedRoutes = [
        'expert.dashboard',
        'rental-requests.awaiting.pickup',
        'rental-requests.pickup-document',
        'rental-requests.awaiting.return',
        'rental-requests.return-document',
        'rental-requests.edit',
        'rental-requests.details',
        'profile.me',
        'livewire.update',
        'livewire.upload-file',
        'livewire.preview-file',
        'livewire.message',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user && $user->hasRole('driver')) {
            $routeName = $request->route()?->getName();

            if ($routeName && ! in_array($routeName, $this->allowedRoutes, true)) {
                return redirect()->route('expert.dashboard')->with('error', 'Access restricted for driver role.');
            }
        }

        return $next($request);
    }
}
