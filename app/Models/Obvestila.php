<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Obvestila extends Model
{
    protected $table = 'obvestila';

    protected $fillable = ['type', 'is_event', 'school', 'class', 'datum_prikaza', 'datum_obvestila', 'datum_umika', 'title', 'content'];

    protected static function booted()
    {
        static::creating(function ($obvestila) {
            $obvestila->uuid = (string) Str::uuid();
            $obvestila->created_by = session('user.id');
        });
    }
}
