<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureAuthenticated
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
        // اگر کاربر لاگین نکرده باشد
        if (!Auth::check()) {
            // هدایت به صفحه ورود
            return redirect()->route('auth.login');
        }

        // ادامه درخواست برای کاربر لاگین شده
        return $next($request);
    }
}
