<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class EviWeb extends Controller
{
    //
    public function encrypt_user_credits(Request $request){
        $username = $request->username;
        $password = $request->password;

        return response()->json(['success'=>true, 'message' => ['username' => $username, 'password' => Crypt::encryptString($password)]]);

    }

    public function evi_login(Request $request){
        $username = $request->username;
        $password = Crypt::decryptString($request->password);

        $evi_session = Http::withOptions(['verify' => false])->get('https://eportal.sc-celje.si/EWePortal-cgi/EWePortal.exe/getdata?CID=000:-:0000000000&ACT=getPUBCID&PMD=3.079');
        $evi_session = $evi_session->body();
      
        $crawler = new Crawler($evi_session);
            

        // SESSION ID je CID v eviWebu
        $session_id = $crawler->filter('span')->text();
        $session_id = explode(';', $session_id);
        $session_id = $session_id[0];
        $session_id = explode('=', $session_id);

        $session_id = $session_id[1];

        $evi_login = Http::withOptions(['verify' => false])->withBody('CID='.$session_id.'&ACT=PUBlogin&PMD='.$username.'%3B'.$password.'%3B', 'text')->post('https://eportal.sc-celje.si/EWePortal-cgi/EWePortal.exe/postdata');
        $evi_data = $evi_login->body();

        $crawler = new Crawler($evi_data);
        $evi_data = $crawler->filter('span')->text();
        $evi_data = explode(';', $evi_data);
        $evi_data_array = [];
        foreach ($evi_data as $data){
            $data = explode('=', $data);
            array_push($evi_data_array, $data);
        }
        $request->session()->put(['evi_data' => $evi_data_array]);
        return response()->json(['success'=>true, 'message' => $evi_data_array]);
    }

    public function evi_redovalnica(Request $request){
        $evi_data = $request->session()->get('evi_data');
        $session_id = $evi_data[0][1]; // CID
        $user_id = $evi_data[3][1]; // IDD
        $user_program = explode('|', $evi_data[7][1])[0]; // PRS

        $evi_redovalnica = Http::withOptions(['verify' => false])->get('https://eportal.sc-celje.si/EWePortal-cgi/EWePortal.exe/getdata?CID='.$session_id.'&ACT=getPUBGrades&PMD='.$user_id.':P'.$user_program.':');
        $evi_redovalnica = $evi_redovalnica->body();

        $crawler = new Crawler($evi_redovalnica);
        $evi_redovalnica = $crawler->filter('span')->text();
        $evi_redovalnica = explode(';', $evi_redovalnica);
        $evi_redovalnica_array = [];
        foreach ($evi_redovalnica as $data){
            $data = explode('=', $data);
            array_push($evi_redovalnica_array, $data);
        }

        return response()->json(['success'=>true, 'message' => $evi_redovalnica_array]);
    }

    public function evi_testi(Request $request){
        $evi_data = $request->session()->get('evi_data');
        $session_id = $evi_data[0][1]; // CID
        $user_id = $evi_data[3][1]; // IDD
        $user_program = explode('|', $evi_data[7][1])[0]; // PRS

        $evi_testi = Http::withOptions(['verify' => false])->get('https://eportal.sc-celje.si/EWePortal-cgi/EWePortal.exe/getdata?CID='.$session_id.'&ACT=getPUBCrossTab&PMD='.$user_id.':P'.$user_program.':');
        $evi_testi = $evi_testi->body();

        $crawler = new Crawler($evi_testi);
        $evi_testi = $crawler->filter('span')->text();
        $evi_testi = explode(';', $evi_testi);
        $evi_testi_array = [];
        foreach ($evi_testi as $data){
            $data = explode('=', $data);
            array_push($evi_testi_array, $data);
            
        }
        $evi_testi_array = explode('|', $evi_testi_array[2][5]);
        $evi_testi_polje = [];

        for ($i = 0; $i < count($evi_testi_array); $i++) {
            if($i % 3 == 0){
                array_push($evi_testi_polje, array($evi_testi_array[$i], $evi_testi_array[$i+1], str_replace('::SPR', '', $evi_testi_array[$i+2])));
            }
        }

        $variable = [];

        return response()->json(['success'=>true, 'message' => $evi_testi_polje]);
    }
}

