<?php

namespace App\Http\Controllers\Backend;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Backend\HubInCharge;
use App\Repositories\HubInCharge\HubInChargeInterface;
use Illuminate\Http\Request;
use App\Http\Requests\HubInCharge\HubInChargeRequest;
use Brian2694\Toastr\Facades\Toastr;

class HubInChargeController extends Controller
{
    protected $repo;
    public function __construct(HubInChargeInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index($hubID)
    {
        $hubInCharges = $this->repo->all($hubID);
        $hub          = $this->repo->hub($hubID);
        return view('backend.hubincharge.index',compact('hubInCharges','hub'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function create($hubID)
    {
        $hub    = $this->repo->hub($hubID);
        $users  = $this->repo->users();
      return view('backend.hubincharge.create',compact('hub','users'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param HubInChargeRequest $request
     * @param $hubID
     * @return \Illuminate\Http\Response
     */
    public function store(HubInChargeRequest $request, $hubID)
    {

        if($this->repo->store($hubID,$request)){
            Toastr::success(__('incharge.added_msg'),__('message.success'));
            return redirect()->route('hub-incharge.index',$hubID);
        }else{
            Toastr::error(__('incharge.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($hubID,$id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($hubID,$id)
    {
        $hub        = $this->repo->hub($hubID);
        $users      = $this->repo->users();
        $inCharge   = $this->repo->get($hubID,$id);
        return view('backend.hubincharge.edit',compact('inCharge','hub','users'));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param $hubID
     * @param int $id
     * @param HubInChargeRequest $request
     * @return \Illuminate\Http\Response
     */
    public function update($hubID, $id, HubInChargeRequest $request)
    {
        if($this->repo->update($hubID, $id, $request)){
            Toastr::success(__('incharge.update_msg'),__('message.success'));
            return redirect()->route('hub-incharge.index',$hubID);
        }else{
            Toastr::error(__('incharge.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($hubID,$id)
    {


        $this->repo->delete($id);

        Toastr::success(__('incharge.delete_msg'),__('message.success'));
        return back();
    }

    public function assigned($hubID,$id)
    {
        $inCharge                   = $this->repo->get($hubID,$id);
        $queryArray['user_id']      = $inCharge->user_id;
        $queryArray['status']       = Status::ACTIVE;
        $hubInCharge                = HubInCharge::where($queryArray)->where('id', '!=', $id)->first();

        if(!blank($hubInCharge)){
            Toastr::error(__('validation.attributes.user_assigned'),__('message.error'));
            return redirect()->back();
        }
        $queryHubArray['user_id']      = $inCharge->user_id;
        $queryHubArray['hub_id']       = $hubID;
        $userHubUnique = HubInCharge::where($queryHubArray)->where('id', '!=', $id)->first();

        if(!blank($userHubUnique)){
            Toastr::error(__('validation.attributes.user_exists'),__('message.error'));
            return redirect()->back();
        }

        if($this->repo->assignedHub($hubID,$inCharge)){
            Toastr::success(__('incharge.assigned_msg'),__('message.success'));
            return redirect()->route('hub-incharge.index',$hubID);
        }else{
            Toastr::error(__('incharge.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }
}
