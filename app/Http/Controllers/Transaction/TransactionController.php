<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Constants\ResponseConstants;
use App\Http\Controllers\Controller;
use App\Http\Utils\ResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class TransactionController extends Controller
{
    public function __construct()
    { }

    public function loanTransaction(Request $request)
    {
        $data = [];
        try {
            $transaction = new TransactionFacade();
            $transaction->doTransaction($request);

            $data = ResponseConstants::TRANSACTION_SUCCESS;
        } catch (ResponseException $th) {
            $data = $th->getResponse();
        } catch (Throwable $th) {
            $data = ResponseConstants::ERROR;
            $data['error_msg'] = $th->getMessage();
            $data['stactrace'] = $th->getTraceAsString();
        }

        return response()->json($data, $data['status']);
    }
}
