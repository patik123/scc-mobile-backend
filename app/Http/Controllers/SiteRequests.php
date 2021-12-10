<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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

        if($token_decode->exp < time()){
            return response()->json(['error' => 'Token expired'], 401);
        }
        return response()->json(['success' => 'Token valid'], 200);
    }
}
