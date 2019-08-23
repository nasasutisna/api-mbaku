<?php

namespace App\Http\Controllers;
use function GuzzleHttp\json_decode;
use function GuzzleHttp\json_encode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Storage;

class CategoryController extends Controller
{
    /**
     * Class constructor.
     */
    public function __construct()
    {

    }

    public function getCategory(Request $request)
    {
        $page = $request->input('page') ? $request->input('page') : 1;
        $limit = $request->input('limit') ? $request->input('limit') : 10;
        $keyword = $request->input('keyword');

        $skip = ($page == 1) ? $page - 1 : (($page - 1) * $limit);
        $query = DB::table('category');
        $query->skip($skip);
        $query->limit($limit);

        $temp = $query;
        $countRows = $temp->count();
        $totalPage = $countRows <= $limit ? 1 : ceil($countRows / $limit);

        $query = $query->get();

        $data = array(
            'data' => $query,
            'limit' => (int) $limit,
            'page' => (int) $page,
            'total' => $countRows,
            'totalPage' => $totalPage,
        );

        return response()->json($data);
    }

    public function getDetailBook($id)
    {
        $bookID = $id;

        $query = $this->book->select('category.categoryTitle', 'book.*')
            ->leftjoin('category', 'category.categoryID', '=', 'book.categoryID')
            ->where('bookID', $bookID);

        $query = $query->first();
        $data = json_decode(json_encode($query), true);

        return response()->json($data);
    }
}
