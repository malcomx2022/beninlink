<?php

namespace App\Http\Controllers\Backend\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\SignUpRequest;
use App\Http\Requests\Company\StoreRequest;
use App\Http\Requests\Company\UpdateRequest;
use App\Http\Requests\Merchant\OtpRequest;
use App\Models\Backend\Superadmin\Plan;
use App\Models\User;
use App\Repositories\Currency\CurrencyInterface;
use App\Repositories\Superadmin\Company\CompanyInterface;
use App\Repositories\Superadmin\Plan\PlanInterface;
use App\Repositories\User\UserInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    protected $repo,
        $userRepo,
        $currencyRepo,
        $planRepo;
    public function __construct(
        CompanyInterface  $repo,
        UserInterface     $userRepo,
        CurrencyInterface $currencyRepo,
        PlanInterface     $planRepo
    ) {
        $this->repo         = $repo;
        $this->userRepo     = $userRepo;
        $this->currencyRepo = $currencyRepo;
        $this->planRepo     = $planRepo;
    }

    public function index()
    {
        $companies = $this->repo->get();
        return view('backend.super-admin.company.index', compact('companies'));
    }

    public function create()
    {
        $departments  = $this->userRepo->departments();
        $designations = $this->userRepo->designations();
        $currencies   = $this->currencyRepo->getActive();
        $plans        = $this->planRepo->getActive();
        return view('backend.super-admin.company.create', compact('designations', 'departments', 'currencies', 'plans'));
    }

    public function store(StoreRequest $request)
    {
        if ($this->repo->store($request)) {
            Toastr::success('Company successfully added.', __('message.success'));
            return redirect()->route('company.index');
        } else {
            Toastr::error('Something went wrong.', __('message.error'));
            return redirect()->back();
        }
    }

    public function edit($id)
    {
        $departments  = $this->userRepo->departments();
        $designations = $this->userRepo->designations();
        $currencies   = $this->currencyRepo->getActive();
        $plans        = $this->planRepo->getActive();
        $company      = $this->repo->getFind($id);
        return view('backend.super-admin.company.edit', compact('designations', 'departments', 'currencies', 'plans', 'company'));
    }

    public function update(UpdateRequest $request)
    {
        if ($this->repo->update($request->id, $request)) {
            Toastr::success('Company successfully updated.', __('message.success'));
            return redirect()->route('company.index');
        } else {
            Toastr::error('Something went wrong.', __('message.error'));
            return redirect()->back();
        }
    }

    public function delete($id)
    {
        if(env('DEMO')):
            Toastr::error('Delete system is disable for the demo mode.',__('message.error'));
            return redirect()->back();
        endif;
        if ($this->repo->delete($id)) {
            Toastr::success('Company successfully deleted.', __('message.success'));
            return redirect()->route('company.index');
        } else {
            Toastr::error('Something went wrong.', __('message.error'));
            return redirect()->back();
        }
    }

    public function switchSubscription($id)
    {
        $user_id      = $id;
        $user         = User::find($id);
        $plan         = Plan::find($user->company->plan_id);
        $plans        = $this->planRepo->getActive();
        return view('backend.super-admin.company.switch_subscription', compact('user_id', 'plans', 'plan'));
    }

    public function switchSubscriptionStore(Request $request)
    {
        if ($this->repo->switchPlan($request)) {
            Toastr::success('Subscribed successfully.', __('message.success'));
            return redirect()->route('company.index');
        } else {
            Toastr::error('Something went wrong.', __('message.error'));
            return redirect()->back();
        }
    }



    public function signUp(Request $request)
    {
        return view('backend.super-admin.company.company_signup', compact('request'));
    }

    public function signUpStore(SignUpRequest $request)
    {
        if ($this->repo->signUpStore($request)) {
            return redirect()->route('company.otp-verification-form');
        } else {
            Toastr::error('Something went wrong.', __('message.error'));
            return redirect()->back();
        }
    }


    public function otpVerificationForm()
    {
        return view('backend.super-admin.company.verification');
    }

    public function resendOTP(Request $request)
    {
        $this->repo->resendOTP($request);
        return redirect()->route('company.otp-verification-form')->with('success', 'Resend OTP');
    }

 
    public function otpVerification(OtpRequest $request)
    {
        $result     = $this->repo->otpVerification($request);
        if ($result != null) {
            Toastr::success('Successfully verified.', __('message.error')); 
            return redirect()->route('login'); 
        } elseif ($result == 0) {
            return redirect()->route('company.otp-verification-form')->with('warning', 'Invalid OTP');
        } else {
            Toastr::error(__('merchant.error_msg'), __('message.error'));
            return redirect()->back();
        }
    }

    
}
