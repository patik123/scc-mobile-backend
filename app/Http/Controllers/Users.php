<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class Users extends Controller
{
    public function User(Request $request)
    {
        $user = User::firstOrCreate(
            ['email' => $request->email],
            ['id' => $request->id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'class' => $request->class,
            'school' => $request->school,
            'type' => $request->type,
            'eviweb_available' => $request->eviweb_available
            ]);

        return response()->json($user);
    }

    public function UpdateUser(Request $request){
        $user = User::where('id', $request->id)->first();
        $user->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'class' => $request->class,
            'school' => $request->school,
            'type' => $request->type,
            'eviweb_available' => $request->eviweb_available
        ]);

        $user->save();

        return response()->json($user);
    }
}
