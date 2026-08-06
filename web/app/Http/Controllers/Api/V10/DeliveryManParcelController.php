<?php

namespace App\Http\Controllers\Api\V10;

use App\Enums\StatementType;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Backend\DeliverymanStatement;
use App\Repositories\Parcel\ParcelInterface;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeliveryManParcelController extends Controller
{

    use ApiReturnFormatTrait;

    protected $repo;
    public function __construct(ParcelInterface $repo)
    {
        $this->repo = $repo;
    }
    public function index(Request $request)
    {
        try {
            $parcels = $this->repo->deliveryManParcel();
            return $this->responseWithSuccess(__('parcel.title'), ['parcels'=>$parcels], 200);
        }catch (\Exception $exception){
            return $this->responseWithError(__('parcel.title'), [], 500);

        }
    }


    public function details($id)
    {

        try {
            $parcel       = $this->repo->details($id);
            $parcelEvents = $this->repo->parcelEvents($id);
            return $this->responseWithSuccess(__('parcel.parcel_details'), ['parcel'=>$parcel,'parcelEvents'=>$parcelEvents], 200);
        }catch (\Exception $exception){
            return $this->responseWithError(__('parcel.parcel_details'), [], 500);

        }
    }

    public function parcelDelivered($id,Request $request)
    {
        try {
            $this->repo->parcelDelivered($id, $request);
            return $this->responseWithSuccess(__('parcel.delivered_success'), [], 200);
        } catch (\Exception $exception) {
            return $this->responseWithError(__('parcel.error_msg'), [], 500);
        }
    }

    public function deliveryIncomeExpense($id,Request $request)
    {
        $d_income       = DeliverymanStatement::where('type',StatementType::INCOME)->whereBetween('created_at',$this->repo->FromTo($request))->sum('amount');
        $d_expense      = DeliverymanStatement::where('type',StatementType::EXPENSE)->whereBetween('created_at',$this->repo->FromTo($request))->sum('amount');
        try {
            $this->repo->parcelDelivered($id, $request);
            return $this->responseWithSuccess(__('parcel.delivered_success'), [], 200);
        } catch (\Exception $exception) {
            return $this->responseWithError(__('parcel.error_msg'), [], 500);
        }
    }

    public function parcelPartialDelivered($id, Request $request)
    {
        $validator = Validator::make($request->all(),[
            'cash_collection'       => 'required',
        ]);

        if ($validator->fails()) {
            return $this->responseWithError(__('parcel.required'), ['message' => $validator->errors()], 422);
        }

        try {
            $this->repo->parcelPartialDelivered($id, $request);
            return $this->responseWithSuccess(__('parcel.partial_delivered_success'), [], 200);
        }catch (\Exception $exception) {
            return $this->responseWithError(__('parcel.error_msg'), [], 500);

        }
    }


}
