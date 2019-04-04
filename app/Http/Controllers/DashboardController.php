<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    //
    
		public function __construct()
    {
        //
        $this->buku = DB::table('buku');
    }

    function index(){
        $data = $this->buku->orderBy('ratting','desc')->paginate(5);
        return response()->json($data);
		}
		
		function getDataBuku(){
			$data = $this->buku->orderBy('ratting','desc')->paginate(5);
			return response()->json($data);
		}
}
