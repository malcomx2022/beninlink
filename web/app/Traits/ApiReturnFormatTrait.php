<?php

namespace App\Traits;
use Illuminate\Support\Facades\Validator;

trait ApiReturnFormatTrait {

    protected function responseWithSuccess($message='', $data=[], $code = 200){
        return response()->json([
            'success'   => true,
            'message'   => $message,
            'data'      => $data,
        ],$code);
    }

    protected function responseWithError($message='', $data=[], $code=400){
        return response()->json([
            'success'     => false,
            'message'     => $message,
            'data'        => $data,
        ], $code);
    }
}
