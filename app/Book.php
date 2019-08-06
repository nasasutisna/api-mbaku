<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $table = 'book';
    // protected $fillable = [
    //     'kode_anggota','nama_lengkap', 'jenis_kelamin','alamat','email','nomor_handphone','status'
    // ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'bookSerialID',
    ];
}
