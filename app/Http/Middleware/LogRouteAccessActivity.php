<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Jobs\LogRouteAccessJob;

class LogRouteAccessActivity
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {

            if (!$request->routeIs('livewire.*') && !$request->ajax()) {

                // ۱. استخراج داده‌ها به صورت آرایه
                $logData = [
                    'user_id' => Auth::id(),
                    'url'     => $request->fullUrl(),
                    'method'  => $request->method(),
                    'ip'      => $request->ip(),
                ];

                // ۲. ارسال آرایه به صف (Queue)
                LogRouteAccessJob::dispatch($logData);
            }
        }

        return $next($request);
    }
}
