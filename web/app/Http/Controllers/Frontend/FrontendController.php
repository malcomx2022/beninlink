<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Mail\ContactMail;
use App\Repositories\DeliveryCharge\DeliveryChargeInterface;
use App\Repositories\FrontWeb\Blogs\BlogsInterface;
use App\Repositories\FrontWeb\Faq\FaqInterface;
use App\Repositories\FrontWeb\Pages\PagesInterface;
use App\Repositories\FrontWeb\Partner\PartnerInterface;
use App\Repositories\FrontWeb\Service\ServiceInterface;
use App\Repositories\FrontWeb\WhyCourier\WhyCourierInterface;
use App\Repositories\MerchantPanel\MerchantParcel\MerchantParcelInterface;
use App\Repositories\Parcel\ParcelInterface;
use App\Repositories\Role\RoleInterface;
use App\Repositories\Superadmin\Plan\PlanInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
class FrontendController extends Controller
{
    protected $serviceRepo,$whycourierRepo,$deliveryChargeRepo,$partnerRepo,$parcelRepo,$pageRepo,$faqRepo,$MerchantParcelRepo,$blogRepo,$planRepo,$roleRepo;
    public function __construct(
        ServiceInterface        $serviceRepo,
        WhyCourierInterface     $whycourierRepo,
        DeliveryChargeInterface $deliveryChargeRepo,
        PartnerInterface        $partnerRepo,
        ParcelInterface         $parcelRepo,
        PagesInterface          $pageRepo,
        FaqInterface            $faqRepo,
        MerchantParcelInterface $MerchantParcelRepo,
        BlogsInterface          $blogRepo,
        PlanInterface           $planRepo,
        RoleInterface           $roleRepo
        )
    {
        $this->serviceRepo        = $serviceRepo;
        $this->whycourierRepo     = $whycourierRepo;
        $this->deliveryChargeRepo = $deliveryChargeRepo;
        $this->partnerRepo        = $partnerRepo;
        $this->parcelRepo         = $parcelRepo;
        $this->pageRepo           = $pageRepo;
        $this->faqRepo            = $faqRepo;
        $this->MerchantParcelRepo = $MerchantParcelRepo;
        $this->blogRepo           = $blogRepo;
        $this->planRepo           = $planRepo;
        $this->roleRepo           = $roleRepo;
    }
    public function index(){ 
       
        $data = [];
        $data['services']     = $this->serviceRepo->getAll(); 
        $data['whycouriers']  = $this->whycourierRepo->getAll();
        $data['plans']        = $this->planRepo->getActive(); 
        $data['pricing']      = $this->deliveryChargeRepo->getAllCharge(); 
        $data['partners']     = $this->partnerRepo->getAll(); 
        $data['blogs']        = $this->blogRepo->getActive(3); 
        $allModules           = $this->roleRepo->adminPermissionsModules();
        $data['allmodules']   = array_slice( $allModules->toArray(), 0, 10);   
       
        return view('frontend.home',$data);
    }

    public function tracking(Request $request){  
        $parcel         = $this->parcelRepo->parcelTracking($request);
        $parcelevents   = $this->parcelRepo->parcelEvents($parcel->id?? null);
        return view('frontend.pages.tracking',compact('parcelevents','parcel','request'));
    }

    public function aboutUs(){
        $page = $this->pageRepo->get('about_us');
        if(!$page):
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        endif;
        return view('frontend.pages.page',compact('page'));
    }
    public function privacyPolicy(){
        $page = $this->pageRepo->get('privacy_policy');
        if(!$page):
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        endif;
        return view('frontend.pages.page',compact('page'));
    }
 
    public function termsOfCondition(){
        $page = $this->pageRepo->get('terms_conditions');
        if(!$page):
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        endif;
        return view('frontend.pages.page',compact('page'));
    }
    public function faq(){
        $page = $this->pageRepo->get('faq');
        $faqs = $this->faqRepo->getActive();
        if(!$page):
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        endif;
        return view('frontend.pages.faq',compact('page','faqs'));
    }

    public function subscribe(Request $request){
        try { 
        
            $validator  = Validator::make($request->all(),[
                'email'=>['required','email']
            ]);
            if($validator->fails()):
                Toastr::error(__('parcel.error_msg'),__('message.error'));  
                return redirect()->back()->withInput($request->all())->withErrors($validator->errors());
            endif;
            if($this->MerchantParcelRepo->subscribe($request) === true):
                Toastr::success(__('levels.successfully_subscribed'),__('message.success')); 
                return redirect()->back();
            elseif($this->MerchantParcelRepo->subscribe($request) == 1):
                Toastr::error(__('levels.already_subscribed'),__('message.error'));  
                return redirect()->back()->withInput($request->all());
            else:
                Toastr::error(__('parcel.error_msg'),__('message.error'));
                return redirect()->back();
            endif;
         } catch (\Throwable $th) { 
            Toastr::error(__('parcel.error_msg'),__('message.error'));  
            return redirect()->back()->withInput($request->all());
         }
    }


    public function contactSendPage(){
        $page = $this->pageRepo->get('contact'); 
        if(!$page):
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        endif;
        return view('frontend.pages.contact',compact('page'));
    }

    public function contactMessageSend(Request $request){
        try { 
            $validator    =  Validator::make($request->all(),[
                'name'    => ['required'],
                'email'   => ['required','email'],
                'subject' => ['required'],
                'message' => ['required','min:10']
            ]);
            if($validator->fails()):
                Toastr::error(__('parcel.error_msg'),__('message.error'));  
                return redirect()->back()->withInput($request->all())->withErrors($validator->errors());
            endif;
            Mail::send(new ContactMail($request->all()));
            Toastr::success(__('levels.message_sended_successfully'),__('message.success')); 
            return redirect()->back(); 
        } catch (\Throwable $th) { 
            Toastr::error(__('parcel.error_msg'),__('message.error'));  
            return redirect()->back()->withInput($request->all());
        }
    }

    public function blogs(){ 
        $blogs         = $this->blogRepo->getActive(); 
        return view('frontend.pages.blogs_page',compact('blogs'));
    }
    public function blogDetails($id){
        $this->blogRepo->viewcount($id);
        $blog         = $this->blogRepo->getFind($id);
        $latest_blogs = $this->blogRepo->getLatestBlogs();
        return view('frontend.pages.blog_details',compact('blog','latest_blogs'));
    }

    public function serviceDetails($id){
        $service         = $this->serviceRepo->getFind($id);
        $latest_services = $this->serviceRepo->latest_services(); 
        return view('frontend.pages.service_details',compact('service','latest_services'));
    }
}
