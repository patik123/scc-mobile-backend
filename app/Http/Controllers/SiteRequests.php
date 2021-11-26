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
        $response = Http::withOptions(["verify"=>false])->get('https://prehrana.sc-celje.si/login');
        return $response->body();
    }

    /*
    * Pridobi stran šole. Vrne vsebino strani.
    * Zahtevan je url strani šole ?school=ker.sc-celje.si
    */

    public function getSchoolSite(Request $request){
        $url = $request->all()['school'];
        return Http::withOptions(["verify"=>false])->get($url)->body();
    }
}
