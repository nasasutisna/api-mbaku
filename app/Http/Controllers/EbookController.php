<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Storage;

class EbookController extends Controller
{
    public $tbl_ebook = 'ebook';
    public $tbl_feedback = 'feedback';

    public function __construct()
    {
        $this->category = DB::table('category');
        $this->ebook = DB::table('ebook');
    }

    public function getEbookList(Request $request)
    {
        $arrCategory = [];
        $pageIndex = $request->input('pageIndex');
        $pageSize = $request->input('pageSize');
        $sortBy = $request->input('sortBy');

        $filter_category = json_decode($request->input('category'), true);
        $keyword = $request->input('keyword');
        $skip = ($pageIndex == 0) ? $pageIndex : ($pageIndex * $pageSize);

        $query = $this->ebook;
        $query->select('category.categoryTitle as category', 'ebook.*');
        $query->selectRaw('COALESCE((SELECT SUM(feedback.feedbackValue) FROM feedback where feedback.ebookID = ebook.ebookID),0) as feedback');
        $query->leftjoin('category', 'category.categoryID', '=', 'ebook.categoryID');

        $count_page = DB::table('ebook')->count();

        // sort by stok
        if ($sortBy != 'undefined' && $sortBy != '') {
            $sortBy = explode('|', $sortBy);
            $query = $query->orderBy($sortBy[0], $sortBy[1]);
        }

        // filter category
        if ($filter_category != '' && count($filter_category) > 0) {
            foreach ($filter_category as $key => $value) {
                $fil_category = explode('|', $value);
                $fil_category = $fil_category[0];
                $arrCategory[] = $fil_category;
            }
            $query = $query->whereIn('ebook.categoryID', $arrCategory);
            $count_page = count($query->get());
        }

        // searching
        if ($keyword != '' && $keyword != 'undefined') {
            $query = $query->where('ebook.ebookTitle', 'like', '%' . $keyword . '%');
            $query = $query->orWhere('ebook.ebookWriter', 'like', '%' . $keyword . '%');
            $count_page = count($query->get());
        }

        $query->skip($skip);
        $query->limit($pageSize);

        $query = $query->get();
        $query = json_decode(json_encode($query), true);

        $data = array(
            'data' => $query,
            'limit' => $pageSize + 0,
            'page' => $pageIndex + 1,
            'totalPage' => $count_page,
        );

        return response()->json($data);
    }
}
