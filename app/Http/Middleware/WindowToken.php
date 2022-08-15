<?php

namespace App\Http\Middleware;

use App\EnumsAndConsts\HttpStatus;
use App\Models\v1\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WindowToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $wToken = $request->header('Window-Token', config('app.env') === 'local' ? $request->get('Window-Token') : null);
        $user = User::where('window_token', $wToken)->where('window_token', '!=', null)->first();
        if ($user) {
            Auth::login($user);
        } else {
            return abort(HttpStatus::UNAUTHORIZED, 'You are not allowed to access this content.');
        }

        return $next($request);
    }
}
