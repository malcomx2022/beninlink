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
use Illuminate\Support\Facades\Log;

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
        // Chantier 3 : le bouton Mobile Money n'apparaît que si les clés
        // FedaPay de la plateforme sont renseignées (.env).
        // L'abonnement SaaS est encaissé par la PLATEFORME, jamais par le
        // locataire : on interroge le compte plateforme, pas le sien.
        $fedapayEnabled = app(\App\Services\Payments\FedaPayGateway::class)->isEnabled();
        $stripeEnabled  = $this->stripePlatformReady();
        return view('backend.subscription.subscription',compact('plans','allmodules','fedapayEnabled','stripeEnabled'));
    }

    /**
     * S105 — Stripe est prêt pour l'abonnement SaaS quand la PLATEFORME (société 1)
     * a l'interrupteur `stripe_status` actif ET une clé secrète. La vue lisait
     * `$stripe_status->value` sur une ligne qui peut manquer (500 pour tout compte,
     * donc pour un locataire expiré que le middleware renvoie ici) ; et le départ
     * vers Stripe lisait la clé sans la vérifier. Sans l'un des deux, le bouton
     * n'apparaît pas et le départ est refusé — le Mobile Money (FedaPay) reste.
     */
    private function stripePlatformReady(): bool
    {
        $statut = Setting::where('company_id', 1)->where('key', 'stripe_status')->value('value');
        $cle    = Setting::where('company_id', 1)->where('key', 'stripe_secret_key')->value('value');

        return (int) $statut === \App\Enums\Status::ACTIVE && filled($cle);
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
    
        // S105 — sans interrupteur actif et clé de plateforme, on refuse avant
        // d'appeler Stripe (le socle lisait `->value` sur une ligne absente : 500).
        if (!$this->stripePlatformReady()) {
            Toastr::error(__('levels.stripe_not_configured'), __('message.error'));
            return redirect()->route('subscription.index');
        }
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
            // S1 — `{CHECKOUT_SESSION_ID}` est remplacé par Stripe au moment de la
            // redirection. C'est lui qui permet de VÉRIFIER le paiement au retour ;
            // `plan_id` et `user_id` seuls ne prouvent rien, ils viennent de l'URL.
            'success_url' => route('subscription.success',['plan_id'=>$plan->id,'user_id'=>Auth::user()->id]).'&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => route('subscription.cancel'),
        ]);  
        return redirect()->to($session->url);
    }
 
    /**
     * S1 — Retour de Stripe après paiement d'un abonnement.
     *
     * Le socle activait l'abonnement dès l'appel de cette URL : `plan_id` et
     * `user_id` venant de la chaîne de requête, un simple
     * `GET /subscription/success?plan_id=X&user_id=Y` suffisait à s'attribuer
     * n'importe quel plan, gratuitement.
     *
     * Le paiement est désormais **vérifié auprès de Stripe** avant toute
     * activation. Quatre contrôles, chacun fermant une porte :
     *   1. la session existe chez Stripe ;
     *   2. elle est réellement payée (`payment_status = paid`) ;
     *   3. elle appartient à l'utilisateur connecté (`client_reference_id`) —
     *      sans quoi une session payée par un tiers serait réutilisable ;
     *   4. le montant payé correspond au plan demandé — sans quoi on paierait
     *      le plan le moins cher pour activer le plus cher.
     */
    public function StripePaymentSuccess(Request $request){
        $sessionId = $request->query('session_id');
        if(blank($sessionId)):
            Toastr::error(__('account.error_msg'),__('message.error'));
            return redirect()->route('dashboard.index');
        endif;

        $plan = Plan::find($request->plan_id);
        if(!$plan):
            Toastr::error(__('account.error_msg'),__('message.error'));
            return redirect()->route('dashboard.index');
        endif;

        try {
            $stripe_secret_key = Setting::where('company_id',1)->where('key','stripe_secret_key')->first();
            \Stripe\Stripe::setApiKey($stripe_secret_key->value);
            $session = \Stripe\Checkout\Session::retrieve($sessionId);
        } catch (\Throwable $e) {
            Log::warning('Abonnement : session Stripe illisible', ['message' => $e->getMessage()]);
            Toastr::error(__('account.error_msg'),__('message.error'));
            return redirect()->route('dashboard.index');
        }

        $paid       = ($session->payment_status ?? null) === 'paid';
        $sameUser   = (string) ($session->client_reference_id ?? '') === (string) Auth::id();
        // Stripe raisonne en centimes ; le plan est en unités.
        $expected   = (int) round(((float) $plan->price) * 100);
        $sameAmount = (int) ($session->amount_total ?? 0) === $expected;

        if(!$paid || !$sameUser || !$sameAmount):
            Log::warning('Abonnement : activation refusée', [
                'session'   => $sessionId,
                'payé'      => $paid,
                'même_user' => $sameUser,
                'montant_ok'=> $sameAmount,
            ]);
            Toastr::error(__('account.error_msg'),__('message.error'));
            return redirect()->route('dashboard.index');
        endif;

        $this->companyRepo->switchPlan($request);
        Toastr::success('Subscribed successfully.','Success');
        return redirect()->route('dashboard.index');
    }

    public function StripePaymentCancel(Request $request){
        Toastr::error(__('account.error_msg'),__('message.error'));
        return redirect()->back();
    }
 
}
