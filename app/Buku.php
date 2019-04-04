<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Buku extends Model
{
    protected $table = 'buku';
    // protected $fillable = [
    //     'kode_anggota','nama_lengkap', 'jenis_kelamin','alamat','email','nomor_handphone','status'
    // ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'serial_id',
    ];
}
