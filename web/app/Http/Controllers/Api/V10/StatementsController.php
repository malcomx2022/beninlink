<?php

namespace App\Http\Controllers\Api\V10;

use App\Http\Controllers\Controller;
use App\Http\Resources\v10\StatementsResource;
use App\Models\Backend\MerchantStatement;
use App\Models\Backend\Parcel;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StatementsController extends Controller
{
    use ApiReturnFormatTrait;
    public function index(){
        try {
            $statements = MerchantStatement::where('merchant_id',auth()->user()->merchant->id)->orderByDesc('id')->get();

            return $this->responseWithSuccess(__('statements.title'), ['statements'=> StatementsResource::collection($statements)], 200);
        }catch (\Exception $exception){
            return $this->responseWithError(__('statements.title'), [], 500);

        }
    }

    public function filter(Request $request){

        try {

            $id = auth()->user()->merchant->id;

            $parcelID = Parcel::where('tracking_id', $request->parcel_tracking_id)->first();
            $statements = MerchantStatement::where('merchant_id', $id)->orderByDesc('id')->where(function ($query) use ($request, $parcelID) {

                if ($request->date) {
                    $date = explode('To', $request->date);
                    if (is_array($date)) {
                        $from = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                        $to = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                        $query->whereBetween('created_at', [$from, $to]);
                    }
                }

                if ($request->type) {
                    $query->where('type', $request->type);
                }

                if (!blank($parcelID)) {
                    $query->where(['parcel_id' => $parcelID->id]);
                }
                if ($request->parcel_tracking_id && blank($parcelID)) {
                    $query->where(['parcel_id' => 0]);
                }

            })->get();

            return $this->responseWithSuccess(__('statements.title'), ['statements'=> StatementsResource::collection($statements)], 200);

        }catch (\Exception $exception) {

            return $this->responseWithError(__('statements.title'), [], 500);

        }

    }
}
