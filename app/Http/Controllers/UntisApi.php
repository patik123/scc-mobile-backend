<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


class UntisApi extends Controller
{
    private static function id(){
        $id = time().rand();
        $id = md5($id);
        return $id;
    }

    private static function get_data( $data ){

        $sessionId = self::AuthUntis();
        $response = Http::withOptions(['verify' => false])->withBody(json_encode($data), 'application/json')->withHeaders(['Cookie' => 'JSESSIONID='.$sessionId ])->post(env('UNTIS_URL').'/WebUntis/jsonrpc.do?school='. env('UNTIS_SCHOOL_NAME'));
   
        $response = $response->body();

        $response = json_decode($response, true);

        return $response;
    }

    private function AuthUntis()
    {
        $username = env('UNTIS_USERNAME');
        $password = env('UNTIS_PASSWORD');
        $data = [
            'id' => self::id(),
            'method' => 'authenticate',
            'params' => [
                'user' => $username,
                'password' => $password,
                'client' => 'web',
            ],
            "jsonrpc" => "2.0"
        ];

        $response = Http::withOptions(['verify' => false])->withBody(json_encode($data), 'application/json')->post(env('UNTIS_URL').'/WebUntis/jsonrpc.do?school='. env('UNTIS_SCHOOL_NAME'));
   
        $response = $response->body();

        $response = json_decode($response, true);

        return $response['result']['sessionId'];
    }

    public function get_classes(){
        $data = [
            'id' => self::id(),
            'method' => 'getKlassen',
            'params' => [],
            "jsonrpc" => "2.0"
        ];
       return self::get_data($data);
    }

    public function get_teachers(){
        $data = [
            'id' => self::id(),
            'method' => 'getTeachers',
            'params' => [],
            "jsonrpc" => "2.0"
        ];

        return self::get_data($data);
    }

    public function get_rooms(){
      
        $data = [
            'id' => self::id(),
            'method' => 'getRooms',
            'params' => [],
            "jsonrpc" => "2.0"
        ];

        return self::get_data($data);
    }

    public function get_subjects(){
        $data = [
            'id' => self::id(),
            'method' => 'getSubjects',
            'params' => [],
            "jsonrpc" => "2.0"
        ];

        return self::get_data($data);
    }

    public function get_time_grid(){
        $data = [
            'id' => self::id(),
            'method' => 'getTimegridUnits',
            'params' => [],
            "jsonrpc" => "2.0"
        ];
        return self::get_data($data);
    }

    public function get_status_data(){
        $data = [
            'id' => self::id(),
            'method' => 'getStatusData',
            'params' => [],
            "jsonrpc" => "2.0"
        ];
        return self::get_data($data);
    }

    public function get_departments(){
        $data = [
            'id' => self::id(),
            'method' => 'getDepartments',
            'params' => [],
            "jsonrpc" => "2.0"
        ];
        return self::get_data($data);
    }

    public function get_holidays(){
        $data = [
            'id' => self::id(),
            'method' => 'getHolidays',
            'params' => [],
            "jsonrpc" => "2.0"
        ];
        return self::get_data($data);
    }

    public function get_current_year(){
        $data = [
            'id' => self::id(),
            'method' => 'getCurrentSchoolyear',
            'params' => [],
            "jsonrpc" => "2.0"
        ];
        return self::get_data($data);
    }

    public function get_latest_update_time(){
        $data = [
            'id' => self::id(),
            'method' => 'getLatestImportTime',
            'params' => [],
            "jsonrpc" => "2.0"
        ];
        return self::get_data($data);
    }

    public function get_class_timetable(Request $request){    
        $class_id = $request->all()['class_id'];
        $start_date = $request->all()['start_date'];
        $end_date = $request->all()['end_date'];
        $data = [
            'id' => self::id(),
            'method' => 'getTimetable',
            'params' => [
                'options' => [
                    "element" => [
                        "id" => $class_id,
                        "type" => "1"
                    ],
                    "startDate" => $start_date,
                    "endDate" => $end_date,
                    "showInfo" => true,
                    "showSubstText" => true,
                    "showLsText" => true,
                    "klasseFields" => ["id", "name", "longname", "externalkey"],
                    "roomFields" => ["id", "name", "longname", "externalkey"],
                    "subjectFields" => ["id", "name", "longname", "externalkey"],
                    "teacherFields" => ["id", "name", "longname", "externalkey"]
                ],
            ],
            "jsonrpc" => "2.0"
        ];
        return self::get_data($data);
    }

    public function get_teacher_timetable(Request $request){
        $teacher_id = $request->all()['teacher_id'];
        $start_date = $request->all()['start_date'];
        $end_date = $request->all()['end_date'];
        $data = [
            'id' => self::id(),
            'method' => 'getTimetable',
            'params' => [
                'options' => [
                    "element" => [
                        "id" => $teacher_id,
                        "type" => "2"
                    ],
                    "startDate" => $start_date,
                    "endDate" => $end_date,
                    "showInfo" => true,
                    "showSubstText" => true,
                    "showLsText" => true,
                    "klasseFields" => ["id", "name", "longname", "externalkey"],
                    "roomFields" => ["id", "name", "longname", "externalkey"],
                    "subjectFields" => ["id", "name", "longname", "externalkey"],
                    "teacherFields" => ["id", "name", "longname", "externalkey"]
                ],
            ],
            "jsonrpc" => "2.0"
        ];
        return self::get_data($data);
    }   

    public function search(Request $request){
        $first_name = $request->all()['first_name'];
        $last_name = $request->all()['last_name'];
        $type = $request->all()['type'];

        $data = [
            'id' => self::id(),
            'method' => 'getPersonId',
            'params' => [
                "sn" => $last_name,
                "type" => $type,
                "dob" => 0,
                "fn" => $first_name
            ],
            "jsonrpc" => "2.0"
        ];

        return self::get_data($data);
    }
}
