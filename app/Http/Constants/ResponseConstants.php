<?php

namespace App\Http\Constants;

class ResponseConstants
{
    const ERROR = array('status' => 500, 'msg' => 'Terjadi kesalahan pada sistem, silahkan hubungi administrator.');
    const SUCCESS = array('status' => 200, 'msg' => 'Sukses mendapatkan informasi akun.');

    const REGISTRATION_SUCCESS = array('status' => 200, 'msg' => 'Verifikasi email sudah terkirim ke kotak masuk email Anda.');
    const REGISTRATION_EMAIL_ALREADY_EXISTS = array('status' => 400, 'msg' => 'Email sudah terdaftar, silahkan masukan email lain.');
    const REGISTRATION_NEED_VERIFY = array('status' => 400, 'msg' => 'Email belum diverifikasi, silahkan lakukan verifikasi email Anda.');

    const VERIFY_SUCCESS  = array('status' => 200, 'msg' => 'Selamat, Verifikasi email Kamu berhasil. Sekarang Kamu sudah menjadi bagian dari sahabat MBAKU. Yuk cari buku di perpustakaan terdekat dengan aplikasi mobile MBAKU.');
    const VERIFY_SIGNATURE_INVALID = array('status' => 400, 'msg' => 'Signature Key yang digunakan tidak valid.');
    const VERIFY_USER_NOT_FOUND = array('status' => 400, 'msg' => 'User tidak ditemukan.');
    const VERIFY_USER_EXPIRY = array('status' => 400, 'msg' => 'Link verifikasi email telah kadaluarsa, Silahkan melakukan registrasi ulang akun MBAKU.');

    const LOGIN_SUCCESS = array('status' => 200, 'msg' => 'Login berhasil.');
    const LOGIN_INVALID_PASSWORD = array('status' => 400, 'msg' => 'Maaf password yang anda masukan salah.');
    const LOGIN_USER_NOT_FOUND = array('status' => 400, 'msg' => 'Maaf email yang anda masukan belum terdaftar.');

    const RESET_REQUEST_SUCCESS = array('status' => 200, 'msg' => 'Link reset password berhasil dikirim ke alamat email anda.');
    const RESET_PASSWORD_SUCCESS = array('status' => 200, 'msg' => 'Reset password berhasil.');
    const RESET_ALREADY_SENT = array('status' => 400, 'msg' => 'Permintaan reset password sudah dikirim sebelumnya, silahkan cek kembali email anda.');
    const RESET_LINK_EXPIRED = array('status' => 400, 'msg' => 'Maaf link expired, silahkan lakukan permintaan reset password kembali');

    const SUBMISSION_SUCCESS = array('status' => 200, 'msg' => 'Pengajuan anda sedang kami proses, silahkan tunggu persetujuan maksimal 2 hari kerja.');
    const SUBMISSION_APPROVE_SUCCESS = array('status' => 200, 'msg' => 'Pengajuan Upgrade berhasil DITERIMA.');
    const SUBMISSION_REJECT_SUCCESS = array('status' => 200, 'msg' => 'Pengajuan Upgrade DITOLAK, Informasi terkait penolakan sudah di email ke member tersebut');

    const TRANSACTION_SUCCESS = array('status' => 200, 'msg' => 'Transaksi peminjaman berhasil.');
    const TRANSACTION_RETURN_SUCCESS = array('status' => 200, 'msg' => 'Transaksi pengembalian berhasil.');
    const TRANSACTION_MEMBER_NOT_PREMIUM = array('status' => 400, 'msg' => 'Akun member ini belum premium, silahkan upgrade terlebih dahulu untuk dapat melakukan transaksi peminjaman.');
    const TRANSACTION_LOAN_ALREADY_EXIST = array('status' => 400, 'msg' => 'Akun member ini sedang meminjam buku, silahkan mengembalikan buku yang dipinjam terlebih dahulu.');
    const TRANSACTION_LIBRARY_SETTING_NOT_EXIST = array('status' => 400, 'msg' => 'Pengaturan Perpustakaan belum diinput, silahkan diinput terlebih dahulu.');
    const TRANSACTION_INSUFFICIENT_SALDO = array('status' => 400, 'msg' => 'Maaf saldo member tidak mencukupi, silahkan topup saldo terlebih dahulu.');
}