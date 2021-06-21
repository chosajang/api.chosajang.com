<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Http\CommonLib;

use Illuminate\Support\Facades\Redirect;

class ApiAuthenticate
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
        if(CommonLib::token_check()){
            return $next($request);
        }else{
            return Redirect::to("/notPermission");
        }
        
    }
}
