<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SiteRequests extends Controller
{
    
    /*
    * Pridobi jedilnik. Vrne podatke o jedilniku.
    */
    public function getPrehranaWebsite(){
        return Http::withOptions(["verify"=>false])->get('https://prehrana.sc-celje.si/login')->body();
    }

    /*
    * Pridobi stran šole. Vrne vsebino strani.
    * Zahtevan je url strani šole ?url=ker.sc-celje.si
    */

    public function getSchoolSite(Request $request){
        $url = $request->all()['url'];
        return Http::withOptions(["verify"=>false])->get($url)->body();
    }

    public function validateJWTToken(Request $request){
        if(!$request->hasHeader('Authorization')){
            return response()->json(['error' => 'No token provided.'], 401);
        }
        $token = $request->bearerToken();
        $token = str_replace('Bearer ', '', $token);
        $token_decode = json_decode(base64_decode(str_replace('_', '/', str_replace('-','+',explode('.', $token)[1]))));

        $auth_check_request = Http::withToken($token)->acceptJson()->get('https://graph.microsoft.com/v1.0/me');
        $status_code = $auth_check_request->status();
        $auth_check_response = $auth_check_request->json();

        if($status_code == 200){
            if($request->session()->has('user')){
                return response()->json(['success' => 'Token is valid.'], 200);
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
                return response()->json(['success' => 'Token is valid.'], 200);
            }
        }else{
            return response()->json(['error' => 'Token is invalid.'], 401);
        }

    }
}
