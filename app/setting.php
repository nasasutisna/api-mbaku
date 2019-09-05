<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'setting';
    // protected $fillable = [
    //     'kode_anggota','nama_lengkap', 'jenis_kelamin','alamat','email','nomor_handphone','status'
    // ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    public $timestamps = false;
    public function getUpdatedAtColumn() {
        return null;
    }

    public function getCreatedAtColumn() {
        return null;
    }
    protected $hidden = [
        'settingID',
    ];
}
