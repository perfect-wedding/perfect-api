<?php

namespace App\Http\Middleware;

use App\EnumsAndConsts\HttpStatus;
use App\Traits\Meta;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IsAdmin
{
    use Meta;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::user()->role !== 'admin') {
            if (! $request->expectsJson()) {
                return abort(HttpStatus::FORBIDDEN);
            }

            return $this->buildResponse([
                'message' => 'You are not authorized to access this page.',
                'status' => 'error',
                'status_code' => HttpStatus::FORBIDDEN,
            ]);
        }

        return $next($request);
    }
}
