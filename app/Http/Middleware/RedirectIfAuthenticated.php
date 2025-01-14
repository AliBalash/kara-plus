<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // اگر کاربر لاگین کرده باشد
        if (Auth::check()) {
            // هدایت به داشبورد
            return redirect()->route('expert.dashboard');
        }

        // ادامه درخواست برای کاربر غیرلاگین
        return $next($request);
    }
}
