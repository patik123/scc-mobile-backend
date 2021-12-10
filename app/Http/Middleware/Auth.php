<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Auth
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
        if(!$request->hasHeader('Authorization')){
            return response()->json(['error' => 'No token provided.'], 401);
        }
        $token = $request->bearerToken();
        $token = str_replace('Bearer ', '', $token);
        $token_decode = json_decode(base64_decode(str_replace('_', '/', str_replace('-','+',explode('.', $token)[1]))));

        if($token_decode->exp < time()){
            return response()->json(['error' => 'Token expired'], 401);
        }
          return $next($request);   
    }
}
