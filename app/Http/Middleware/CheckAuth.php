<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CheckAuth
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

        if($request->session()->has('user')){
            $user = $request->session()->get('user');
            if($user['token'] == $token){
                return $next($request);
            }
        }
        $auth_check_request = Http::withToken($token)->acceptJson()->get('https://graph.microsoft.com/v1.0/me');
        $status_code = $auth_check_request->status();
        $auth_check_response = $auth_check_request->json();

        if($status_code == 200){
            if($request->session()->has('user')){
                return $next($request);  
            }
            else{
                if(Str::contains($auth_check_response['userPrincipalName'], '@sc-celje.si')){
                    $user_status = 'ucitelj';
                }
                else if(Str::contains($auth_check_response['userPrincipalName'], '@dijak.sc-celje.si')){
                    $user_status = 'dijak';
                }
                else{
                    $user_status = 'neznano';
                }
                $user = [
                    'id' => $auth_check_response['id'],
                    'name' => $auth_check_response['displayName'],
                    'token' => $token,
                    'mail' => $auth_check_response['mail'],
                    'status' => $user_status
                ];
                $request->session()->put(['user' => $user]);
                return $next($request);  
            }
        }else{
            return response()->json(['error' => 'Token is invalid.'], 401);
        }
    }
}

