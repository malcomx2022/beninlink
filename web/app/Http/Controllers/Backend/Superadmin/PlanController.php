<?php

namespace App\Http\Controllers\Backend\Superadmin;

use App\Enums\BooleanStatus;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Plan\StoreRequest;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\Setting;
use App\Models\Backend\Subscription;
use App\Models\Backend\Superadmin\Plan;
use App\Models\Permission;
use App\Models\User;
use App\Repositories\Role\RoleInterface;
use App\Repositories\Superadmin\Company\CompanyInterface;
use App\Repositories\Superadmin\Plan\PlanInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlanController extends Controller
{
    protected $repo,$roleRepo,$companyRepo;
    public function __construct(PlanInterface $repo,RoleInterface $roleRepo,CompanyInterface $companyRepo)
    {
        $this->repo     = $repo;
        $this->roleRepo = $roleRepo;
        $this->companyRepo = $companyRepo;
    }
    public function index (){
        $plans = $this->repo->get();
        return view('backend.super-admin.plan.index',compact('plans'));
    }
    public function create (){
        $modules = $this->roleRepo->adminPermissionsModules();
        return view('backend.super-admin.plan.create',compact('modules'));
    }
    public function store (StoreRequest $request){

        if($this->repo->store($request)){
            Toastr::success('Plan created successfully.',__('message.success'));
            return redirect()->route('plan.index');
        }else{
            Toastr::error(__('account.error_msg'),__('message.error'));
            return redirect()->back();
        }
        
    }
    public function edit ($id){
        $plan = $this->repo->getFind($id);
        $modules = $this->roleRepo->adminPermissionsModules();
        return view('backend.super-admin.plan.edit',compact('plan','modules'));
    }
    public function update (StoreRequest $request){
        if($this->repo->update($request->id,$request)){
            Toastr::success('Plan updated successfully.',__('message.success'));
            return redirect()->route('plan.index');
        }else{
            Toastr::error(__('account.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }
    public function delete ($id){
        if($this->repo->delete($id)){
            Toastr::success('Plan deleted successfully.',__('message.success'));
            return redirect()->route('plan.index');
        }else{
            Toastr::error(__('account.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function modulesView($plan_id){
        $plan = $this->repo->getFind($plan_id);
        return view('backend.super-admin.plan.plan_modules',compact('plan'));
    }
 
    public function subscription(){ 
        $plans       = $this->repo->getActive();
        $allmodules  = $this->roleRepo->adminPermissionsModules();
        return view('backend.subscription.subscription',compact('plans','allmodules'));
    }
 
    public function subscriptionHistory(Request $request){

          
        $subscriptions = Subscription::where(function($query)use($request){
            if(Auth::user()->user_type != UserType::SUPER_ADMIN): 
                $query->where('company_id',settings()->id);
            endif;
            if($request->company_id):
                $query->where('company_id',$request->company_id);
            endif;
        })->paginate(10);

        $companies = GeneralSettings::where(function($query){ 
            $query->whereNot('id',1);  
        })->get();

        return view('backend.subscription.subscription_history',compact('subscriptions','request','companies'));
    }
 

    public function subscriptionPayment(Request $request){
    
        $stripe_secret_key        =  Setting::where('company_id',1)->where('key','stripe_secret_key')->first(); 
        $plan  = Plan::find($request->plan_id);
        if(!$plan):
            Toastr::error(__('account.error_msg'),__('message.error'));
            return redirect()->back();
        endif; 
        \Stripe\Stripe::setApiKey($stripe_secret_key->value); 
 
        $session = \Stripe\Checkout\Session::create([ 
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'USD',
                        'product_data' => [     
                            'name' => "Payment"
                        ],
                        'unit_amount' => (double)$plan->price * 100?? 0,
                    ],
                    'quantity' => 1,
                ]
            ], 
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'client_reference_id' => Auth::user()->id,  
            'success_url' => route('subscription.success',['plan_id'=>$plan->id,'user_id'=>Auth::user()->id]),
            'cancel_url'  => route('subscription.cancel'),
        ]);  
        return redirect()->to($session->url);
    }
 
    public function StripePaymentSuccess(Request $request){ 
        $this->companyRepo->switchPlan($request);
        Toastr::success('Subscribed successfully.','Success');
        return redirect()->route('dashboard.index');
    }

    public function StripePaymentCancel(Request $request){
        Toastr::error(__('account.error_msg'),__('message.error'));
        return redirect()->back();
    }
 
}
