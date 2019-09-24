<?php

namespace App\Http\Constants;

class ResponseConstants
{
    const ERROR = array('status' => 500, 'msg' => 'Terjadi kesalahan pada sistem, silahkan hubungi administrator.');

    const REGISTRATION_SUCCESS = array('status' => 200, 'msg' => 'Verifikasi email sudah terkirim ke kotak masuk email Anda.');
    const REGISTRATION_EMAIL_ALREADY_EXISTS = array('status' => 400, 'msg' => 'Email sudah terdaftar, silhkan masukan email lain.');
    const REGISTRATION_NEED_VERIFY = array('status' => 400, 'msg' => 'Email menunggu konfirmasi, silhkan lakukan verifikasi email Anda.');

    const VERIFY_SUCCESS  = array('status' => 200, 'msg' => 'Selamat, Verifikasi email Kamu berhasil. Sekarang Kamu sudah menjadi bagian dari sahabat MBAKU. Yuk cari buku di perpustakaan terdekat dengan aplikasi mobile MBAKU.');
    const VERIFY_SIGNATURE_INVALID = array('status' => 400, 'msg' => 'Signature Key yang digunakan tidak valid.');
    const VERIFY_USER_NOT_FOUND = array('status' => 400, 'msg' => 'User tidak ditemukan.');
    const VERIFY_USER_EXPIRY = array('status' => 400, 'msg' => 'Link verifikasi email telah kadaluarsa, Silahakan melakukan registrasi ulang akun MBAKU.');
}
