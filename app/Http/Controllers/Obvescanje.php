<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Obvestila;

class Obvescanje extends Controller
{
    public function create_obvestilo(Request $request){

     $novo_obvestilo =  Obvestila::create([
            'type' => $request->type,
            'is_event' => $request->is_event,
            'school' => $request->school,
            'class' => $request->class,
            'datum_prikaza' => $request->datum_prikaza,
            'datum_obvestila' => $request->datum_obvestila,
            'datum_umika' => $request->datum_umika,
            'title' => $request->title,
            'content' => $request->content,
        ]);

        if($novo_obvestilo){
            return response()->json(['success'=>true, 'message'=>'Obvestilo uspešno dodano!']);
        }else{
            return response()->json(['error'=> true, 'message'=> 'Napaka pri shranjevanju obvestila']);
        }
    }

    public function get_obvestila_global(Request $request){
        $obvestila = Obvestila::where([
            ['type', '=', 'global'],
            ['school', '=', $request->school],
            
        ])->get();

        return response()->json($obvestila);
    }
}
