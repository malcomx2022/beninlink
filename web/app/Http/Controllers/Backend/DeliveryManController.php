<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Repositories\DeliveryMan\DeliveryManInterface;
use Illuminate\Http\Request;
use App\Http\Requests\DeliveryMan\DeliveryManRequest;
use App\Models\Backend\DeliveryMan;
use Brian2694\Toastr\Facades\Toastr;
class DeliveryManController extends Controller
{
    protected $repo;
    public function __construct(DeliveryManInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {
        $deliveryMans = $this->repo->all();
        return view('backend.deliveryman.index',compact('deliveryMans','request'));
    }
    public function filter(Request $request)
    {
        $deliveryMans = $this->repo->filter($request);
        return view('backend.deliveryman.index',compact('deliveryMans','request'));
    }

    public function create()
    {
        $hubs         = $this->repo->hubs();
      return view('backend.deliveryman.create',compact('hubs'));
    }


    public function store(DeliveryManRequest $request)
    {

        $deliveryman_count = DeliveryMan::companywise()->count();
        if(!settings()->subscription): 
            Toastr::error('Something went wrong!', 'Error');
            return redirect()->back()->withInput($request->all());
        elseif(settings()->subscription && settings()->subscription->deliveryman_count <= $deliveryman_count ):
            Toastr::error('You have limited deliveryman manage. Please upgrade your package.', 'Error');
            return redirect()->back()->withInput($request->all()); 
        endif;
 
        if($this->repo->store($request)){
            Toastr::success(__('deliveryman.added_msg'),__('message.success'));
            return redirect()->route('deliveryman.index');
        }else{
            Toastr::error(__('deliveryman.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $hubs         = $this->repo->hubs();
        $deliveryman = $this->repo->get($id);
        return view('backend.deliveryman.edit',compact('deliveryman','hubs'));
    }

    public function update(DeliveryManRequest $request)
    {
        if($this->repo->update($request->id, $request)){
            Toastr::success(__('deliveryman.update_msg'),__('message.success'));
            return redirect()->route('deliveryman.index');
        }else{
            Toastr::error(__('deliveryman.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        Toastr::success(__('deliveryman.delete_msg'),__('message.success'));
        return back();
    }
}
