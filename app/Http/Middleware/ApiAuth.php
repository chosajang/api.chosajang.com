<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Http\CommonLib;

class ApiAuth
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
        if (! $request->expectsJson()) {
            return redirect()->route('error', ['error_code' => 0]);
        }
        
        if( CommonLib::auth_check() ) {
            return $next($request);
        } else {
            return redirect('/error');
        }
    }
}
