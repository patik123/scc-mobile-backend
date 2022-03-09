<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;

class EviWeb extends Controller
{

    // Šifrira uporabnikovo ime in geslo 
    public function encrypt_user_credits(Request $request){
        $username = $request->username;
        $password = $request->password; 
      
        $evi_version = $this->evi_version();

        
        $evi_session = Http::withOptions(['verify' => false])->get('https://eportal.sc-celje.si/EWePortal-cgi/EWePortal.exe/getdata?CID=000:-:0000000000&ACT=getPUBCID&PMD='.$evi_version.'');
        $evi_session = $evi_session->body();
      
        $crawler = new Crawler($evi_session);

        // SESSION ID je CID v eviWebu
        $session_id = $crawler->filter('span')->text();
        $session_id = explode(';', $session_id);
        $session_id = $session_id[0];
        $session_id = explode('=', $session_id);

        $session_id = $session_id[1]; // Pridobi CID

        // Izvede prijavo v EviWeb
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

        // Če so le trije elementi v polju $evi_data_array, je prijava neuspešna vrne napako
        if( count($evi_data_array) == 3){
            return response()->json(['success'=>false, 'error' => 'Prijava ni uspela!'], 401);
        }

        return response()->json(['success'=>true, 'message' => ['username' => $username, 'password' => Crypt::encryptString($password)]], 200);
    }

    // Pridobi trenutno verzijo EviWeba
    public function evi_version(){
        $evi_version = Http::withOptions(['verify' => false])->get('https://eportal.sc-celje.si/EWePortal/scripts/EWePortal.js');
        $evi_version = explode(';', $evi_version)[0];
        $evi_version = str_replace('var PUV="', '', $evi_version);
        $evi_version = str_replace('"', '', $evi_version);

        return $evi_version;
    }

    private function evi_login($username, $password){
        try{
            $password =  Crypt::decryptString($password);
        }
        catch(DecryptException $e){
            return "Napaka pri prijavi";
        }

        if(Cache::has('eviweb_version')){
            $evi_version = Cache::get('eviweb_version');
        }
        else {
            $evi_version = $this->evi_version();
            Cache::put('eviweb_version', $evi_version,  now()->addHours(5));
        }

        $evi_session = Http::withOptions(['verify' => false])->get('https://eportal.sc-celje.si/EWePortal-cgi/EWePortal.exe/getdata?CID=000:-:0000000000&ACT=getPUBCID&PMD='.$evi_version.'');
        $evi_session = $evi_session->body();
      
        $crawler = new Crawler($evi_session);

        // SESSION ID je CID v eviWebu
        $session_id = $crawler->filter('span')->text();
        $session_id = explode(';', $session_id);
        $session_id = $session_id[0];
        $session_id = explode('=', $session_id);

        $session_id = $session_id[1]; // Pridobi CID

        // Izvede prijavo v EviWeb
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

        // Če so le trije elementi v polju $evi_data_array, je prijava neuspešna vrne napako
        if(count($evi_data_array) == 3){
            return "Napaka pri prijavi";
        }

        // Če pa je prijava uspešna vrne podatke o prijavljenem uporabniku
        return $evi_data_array;
    }

    public function evi_logout($cid, $user_id){
        $evi_logout = Http::withOptions(['verify' => false])->get('https://eportal.sc-celje.si/EWePortal-cgi/EWePortal.exe/getdata?CID=' . $cid. '&ACT=PUBlogout&PMD=' .$user_id . ':');
        $evi_data = $evi_logout->body();

        $crawler = new Crawler($evi_data);
        $evi_data = $crawler->filter('span')->text();
        $evi_data = explode(';', $evi_data);
        $evi_data_array = [];
        foreach ($evi_data as $data){
            $data = explode('=', $data);
            array_push($evi_data_array, $data);
        }
        return true;
    }

    public function evi_available(Request $request){
        $username = $request->username;
       $password =  $request->password;

       $evi_data = $this->evi_login($username, $password);

       // Preveri napako če je
       if($evi_data == "Napaka pri prijavi"){
           return false;
       }
        return true;
   }

    public function evi_redovalnica(Request $request){
        $username = $request->username;
        $password =  $request->password;

        $evi_data = $this->evi_login($username, $password);

        // Preveri napako če je
        if($evi_data == "Napaka pri prijavi"){
            return response()->json(['success'=>false, 'message' => 'Napačno uporabniško ime ali geslo.'], 401);
        }

        // Ustvari podatke o seji v EviWebu
        $session_id = $evi_data[0][1]; // CID 
        $user_id = $evi_data[3][1]; // IDD
        $user_program = explode('|', $evi_data[7][1])[0]; // PRS - program izobraževanja

        // Izvede zahtevo za ocene prijavljenega uporabnika.
        $evi_redovalnica = Http::withOptions(['verify' => false])->get('https://eportal.sc-celje.si/EWePortal-cgi/EWePortal.exe/getdata?CID='.$session_id.'&ACT=getPUBGrades&PMD='.$user_id.':P'.$user_program.':'); // Vprašaj Slemenška če je vedno pri programu P
        $evi_redovalnica = $evi_redovalnica->body();

        $crawler = new Crawler($evi_redovalnica);
        $evi_redovalnica = $crawler->filter('span')->text();
        $evi_redovalnica = explode(';', $evi_redovalnica);
        $evi_redovalnica_array = [];
        $evi_predmeti_array = [];
        foreach ($evi_redovalnica as $data){
            $data = explode('=', $data);
            array_push($evi_redovalnica_array, $data);
        }

        
        $evi_predmeti_array = explode('|', $evi_redovalnica_array[4][4]);
        $evi_redovalnica_array = explode('|', $evi_redovalnica_array[4][7]);
        $evi_redovalnica_polje = [];
        $evi_predmeti_polje = [];
        $counter = 0;

        for ($i = 0; $i < count($evi_predmeti_array); $i++) {
            if($i % 4 == 0 || $i == 0){
                $ime_predmeta = explode('?', $evi_predmeti_array[$i+1]);
                array_push($evi_predmeti_polje, array(  (string)$counter,
                  										$evi_predmeti_array[$i], // ID predmeta
                                                        $ime_predmeta[0], // Kratko ime predmeta
                                                        $ime_predmeta[1], // Dolgo ime predmeta
                                                        $evi_predmeti_array[$i+2], 
                                                        str_replace('::RER', '', $evi_predmeti_array[$i+3])   ));
              $counter++;
            }
        }

        $counter = 0;

        for ($i = 0; $i < count($evi_redovalnica_array); $i++) {
            if($i % 10 == 0 || $i == 0){
                array_push($evi_redovalnica_polje, array(   (string)$counter,
                  											$evi_redovalnica_array[$i], // ID ocene
                                                            $evi_redovalnica_array[$i+1],  // ID dijaka
                                                            $evi_redovalnica_array[$i+2],  // ID učitelja
                                                            $evi_redovalnica_array[$i+3], // ID predmeta -> v povezavi v EviWeb predmeti
                                                            $evi_redovalnica_array[$i+4], // Oddelek
                                                            str_replace(' ', '', $evi_redovalnica_array[$i+5]), // Ocena
                                                            $evi_redovalnica_array[$i+6], 
                                                            $evi_redovalnica_array[$i+7], // tip ocene
                                                            $evi_redovalnica_array[$i+8], // Čas vpisa ocene
                                                            str_replace('::KOM', '',$evi_redovalnica_array[$i+9])
                                                        ));
              $counter++;
            }
        }

        $this->evi_logout($session_id, $user_id);
        return response()->json(['success'=>true, 'message' => ['predmeti' => $evi_predmeti_polje, 'ocene' => $evi_redovalnica_polje]]);
    }

    public function evi_predmeti(Request $request){
        $username = $request->username;
        $password = $request->password;

        $evi_data = $this->evi_login($username, $password);

        if($evi_data == "Napaka pri prijavi"){
            return response()->json(['success'=>false, 'message' => 'Napačno uporabniško ime ali geslo.'], 401);
        }

        $session_id = $evi_data[0][1]; // CID
        $user_id = $evi_data[3][1]; // IDD
        $user_program = explode('|', $evi_data[7][1])[0]; // PRS

        $evi_predmeti = Http::withOptions(['verify' => false])->get('https://eportal.sc-celje.si/EWePortal-cgi/EWePortal.exe/getdata?CID='.$session_id.'&ACT=getPUBGrades&PMD='.$user_id.':P'.$user_program.':');
        $evi_predmeti = $evi_predmeti->body();

        $crawler = new Crawler($evi_predmeti);
        $evi_predmeti = $crawler->filter('span')->text();
        $evi_predmeti = explode(';', $evi_predmeti);
        $evi_predmeti_array = [];
        foreach ($evi_predmeti as $data){
            $data = explode('=', $data);
            array_push($evi_predmeti_array, $data);
        }

        if($evi_predmeti_array[1][1] == "Potek seje"){
            return response()->json(['success'=>false, 'message' => 'Niste prijavljeni na eviWebu.'], 401);
        }

        $evi_predmeti_array = explode('|', $evi_predmeti_array[4][4]);
        $evi_predmeti_polje = [];
        $counter = 0;

        for ($i = 0; $i < count($evi_predmeti_array); $i++) {
            if($i % 4 == 0 || $i == 0){
                $ime_predmeta = explode('?', $evi_predmeti_array[$i+1]);
                array_push($evi_predmeti_polje, array(  $counter,
                                                        $evi_predmeti_array[$i], // ID predmeta
                                                        $ime_predmeta[0], // Kratko ime predmeta
                                                        $ime_predmeta[1], // Dolgo ime predmeta
                                                        $evi_predmeti_array[$i+2], 
                                                        str_replace('::RER', '', $evi_predmeti_array[$i+3])   
                                                    ));
                $counter++;
            }
        }
        $this->evi_logout($session_id, $user_id);

        return response()->json(['success'=>true, 'message' => $evi_predmeti_polje]);
    }

    public function evi_testi(Request $request){
        $username = $request->username;
        $password = $request->password;

        $evi_data = $this->evi_login($username, $password);

        if($evi_data == "Napaka pri prijavi"){
            return response()->json(['success'=>false, 'message' => 'Napačno uporabniško ime ali geslo.'], 401);
        }

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

        if($evi_testi_array[1][1] == "Potek seje"){
            return response()->json(['success'=>false, 'message' => 'Niste prijavljeni na eviWebu.'], 401);
        }

        $evi_testi_array = explode('|', $evi_testi_array[2][5]);
        $evi_testi_polje = [];
        $counter = 0;

        for ($i = 0; $i < count($evi_testi_array); $i++) {
            if($i % 3 == 0 || $i == 0){
                array_push($evi_testi_polje, array( $counter,
                                                    $evi_testi_array[$i], // Predmet 
                                                    $evi_testi_array[$i+1], // datum ocenjevanja
                                                    str_replace('::SPR', '', $evi_testi_array[$i+2])
                                                    )
                            );
                $counter++;
            }
        }
        $this->evi_logout($session_id, $user_id);

        return response()->json(['success'=>true, 'message' => $evi_testi_polje]);
    }

    public function evi_ucitelji(Request $request){
        $username = $request->username;
        $password = $request->password;

        $evi_data = $this->evi_login($username, $password);

        if($evi_data == "Napaka pri prijavi"){
            return response()->json(['success'=>false, 'message' => 'Napačno uporabniško ime ali geslo.'], 401);
        }

        $session_id = $evi_data[0][1]; // CID
        $user_id = $evi_data[3][1]; // IDD
        $user_program = explode('|', $evi_data[7][1])[0]; // PRS

        $evi_ucitelji = Http::withOptions(['verify' => false])->get('https://eportal.sc-celje.si/EWePortal-cgi/EWePortal.exe/getdata?CID='.$session_id.'&ACT=getPUBGrades&PMD='.$user_id.':P'.$user_program.':');
        $evi_ucitelji = $evi_ucitelji->body();

        $crawler = new Crawler($evi_ucitelji);
        $evi_ucitelji = $crawler->filter('span')->text();
        $evi_ucitelji = explode(';', $evi_ucitelji);
        $evi_ucitelji_array = [];
        foreach ($evi_ucitelji as $data){
            $data = explode('=', $data);
            array_push($evi_ucitelji_array, $data);
        }

        if($evi_ucitelji_array[1][1] == "Potek seje"){
            return response()->json(['success'=>false, 'message' => 'Niste prijavljeni v EviWeb.'], 401);
        }

        $evi_ucitelji_array = explode('|', $evi_ucitelji_array[4][3]);
        $evi_ucitelji_polje = [];
        $counter = 0;

        for ($i = 0; $i < count($evi_ucitelji_array); $i++) {
            if($i % 9 == 0 || $i == 0){
                array_push($evi_ucitelji_polje, array(      
                                                            $counter,
                                                            $evi_ucitelji_array[$i], // ID profesorja
                                                            $evi_ucitelji_array[$i+1], // Ime profesorja
                                                            $evi_ucitelji_array[$i+2],  // Priimek profesorja
                                                            $evi_ucitelji_array[$i+3],  // Predpona profesorja (mag. ali dr.)
                                                            $evi_ucitelji_array[$i+4], // Mail profesorja
                                                            $evi_ucitelji_array[$i+5], // Kabinet
                                                            $evi_ucitelji_array[$i+6],
                                                            $evi_ucitelji_array[$i+7], // govorilna ura dopoldne
                                                            str_replace('::PRE', '', $evi_ucitelji_array[$i+8]), // govorilna ura popoldne
                                                        ));
                $counter++;
            }
        }
        $this->evi_logout($session_id, $user_id);

        return response()->json(['success'=>true, 'message' => $evi_ucitelji_polje]);
    }

    public function evi_izostanki(Request $request){
        $username = $request->username;
        $password = $request->password;

        $evi_data = $this->evi_login($username, $password);

        if($evi_data == "Napaka pri prijavi"){
            return response()->json(['success'=>false, 'message' => 'Napačno uporabniško ime ali geslo.'], 401);
        }

        $session_id = $evi_data[0][1]; // CID
        $user_id = $evi_data[3][1]; // IDD
        $user_program = explode('|', $evi_data[7][1])[0]; // PRS

        $evi_izostanki = Http::withOptions(['verify' => false])->get('https://eportal.sc-celje.si/EWePortal-cgi/EWePortal.exe/getdata?CID='.$session_id.'&ACT=getPUBViewAbsence&PMD='.$user_id.':P'.$user_program.':');
        $evi_izostanki = $evi_izostanki->body();

        $crawler = new Crawler($evi_izostanki);
        $evi_izostanki = $crawler->filter('span')->text();
        $evi_izostanki = explode(';', $evi_izostanki);
        $evi_izostanki_array = [];
        foreach ($evi_izostanki as $data){
            $data = explode('=', $data);
            array_push($evi_izostanki_array, $data);
        }

        if($evi_izostanki_array[1][1] == "Potek seje"){
            return response()->json(['success'=>false, 'message' => 'Niste prijavljeni v EviWeb.'], 401);
        } 
        
        $evi_izostanki_array = explode(',', $evi_izostanki_array[2][3]);

        $evi_izostanki_array = array_diff($evi_izostanki_array, array('::'));

        $evi_izostanki_polje = [];
        $count_opravicene_ure = 0;
        $count_neopravicene_ure = 0;
        $count_vsoli_ure = 0;
        $count_neobdelane_ure = 0;

        for ($i = 0; $i < count($evi_izostanki_array); $i++) {
            if($i % 4 == 0 || $i == 0){

                if($evi_izostanki_array[$i+2] != '000000000000000'){

                $izostanki_ure = str_split($evi_izostanki_array[$i+2]);

                $opravicene_ure = [];
                $neopravicene_ure = [];
                $vsoli_ure = [];
                $neobdelane_ure = [];

                for($j = 0; $j < count($izostanki_ure); $j++){
                    if($izostanki_ure[$j] == 'o'){
                        array_push($opravicene_ure, $j);
                        $count_opravicene_ure++;
                    }
                    else if($izostanki_ure[$j] == 'n'){
                        array_push($neopravicene_ure, $j);
                        $count_neopravicene_ure++;
                    }
                    else if($izostanki_ure[$j] == 's'){
                        array_push($vsoli_ure, $j);
                        $count_vsoli_ure++;
                    }
                    else if($izostanki_ure[$j] == '1'){
                        array_push($neobdelane_ure, $j);
                        $count_neobdelane_ure++;
                    }
                }
                array_push($evi_izostanki_polje, array(      
                                                        $evi_izostanki_array[$i+1], // datum
                                                           array(
                                                                'opravicene' => $opravicene_ure,
                                                                'neopravicene' => $neopravicene_ure,
                                                                'vsoli' => $vsoli_ure,
                                                                'neobdelane' => $neobdelane_ure
                                                        ),
                                                          
                                                        ));
                }
            }
        }

        $count_ure = array(
        'opravicene' => $count_opravicene_ure,
        'neopravicene' => $count_neopravicene_ure,
        'vsoli' => $count_vsoli_ure,
        'neobdelane' => $count_neobdelane_ure
        );

        $this->evi_logout($session_id, $user_id);

        return response()->json(['success'=> true, 'message' => array('izostanki' => $evi_izostanki_polje, 'count' => $count_ure)]);
    }

        

       
}

