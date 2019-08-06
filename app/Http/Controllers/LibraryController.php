<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Storage;

class LibraryController extends Controller
{
    public $tbl_library = 'library';

    public function __construct()
    {
        $this->library = DB::table('library');
    }

    public function getDetailLibrary($id)
    {
        $libraryID = $id;

        $query = $this->library;
        $query->select('library.*', 'university.universityName' );
        $query->leftjoin('university', 'university.universityID', '=', 'library.universityID');
        $query->where('libraryID', $libraryID);

        $query = $query->first();
        $data = json_decode(json_encode($query), true);

        return response()->json($data);
    }
}
