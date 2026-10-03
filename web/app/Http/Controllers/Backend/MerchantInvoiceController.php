<?php

namespace App\Http\Controllers\Backend;
 
use App\Enums\UserType;
use App\Exports\InvoiceExport;
use App\Http\Controllers\Controller;
use App\Models\Backend\InvoiceParcel;
use App\Models\Backend\Merchantpanel\Invoice;
use App\Repositories\Invoice\InvoiceInterface;
use App\Services\Invoicing\SettlementPdf;
use App\Services\Invoicing\SettlementStatement;
use App\Services\Invoicing\SyscohadaJournal;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Http\Request; 
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class MerchantInvoiceController extends Controller
{
    protected $repo;
    public function __construct(InvoiceInterface $repo){
        $this->repo = $repo;
    }
    public function index($merchantId){
        $invoices    = $this->repo->merchantInvoiceGet($merchantId);
        $merchant_id = $merchantId;
        return view('backend.merchant.invoice.index',compact('invoices','merchant_id'));
    }

    public function InvoiceDetails($merchantId,$invoiceId){
        // S32 — `merchantInvoiceDetails()` est bien `companywise()`, mais hors
        // perimetre il rend `null` et la ligne suivante dereferencait : 500 la ou
        // tout le reste du module repond 404 (famille S15).
        $invoice = $this->repo->merchantInvoiceDetails($merchantId,$invoiceId);
        abort_if(blank($invoice), 404, __('statement.not_found'));

        $invoiceParcels = InvoiceParcel::where('invoice_id',$invoice->id)->paginate(10);
        return view('backend.merchant.invoice.invoice_details', compact('invoice','invoiceParcels'));
    }

    public function StatusUpdate(Request $request,$merchant_id){

        if($this->repo->statusUpdate($request,$merchant_id)):
            Toastr::success(__('invoice.status_updated'),__('message.success'));
            return redirect()->back();
        else:
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        endif;
    }
 

    /**
     * Un marchand du panneau ne télécharge que ses propres relevés.
     *
     * Les routes `pdf`, `csv` et `journal` portent `merchant_id` dans l'URL ;
     * depuis le panneau marchand, le socle ne le recoupait pas avec le
     * compte connecté (S20). L'administration n'est pas concernée.
     */
    private function ownsOrAbort($merchant_id): void
    {
        $user = Auth::user();
        if ($user && (int) $user->user_type === UserType::MERCHANT
            && (int) optional($user->merchant)->id !== (int) $merchant_id) {
            abort(404, __('statement.not_found'));
        }
    }

    /**
     * Relevé de règlement en PDF — chantier 4. Les deux routes du socle
     * (`merchant.invoice.pdf`, `merchant.panel.invoice.pdf`) pointaient vers
     * cette méthode sans qu'elle existe.
     */
    public function InvoicePdf($merchant_id,$invoice_id){
        $this->ownsOrAbort($merchant_id);
        $invoice = $this->repo->invoiceGet($merchant_id,$invoice_id);
        abort_if(blank($invoice), 404, __('statement.not_found'));

        return $this->pdfResponse($invoice);
    }

    /**
     * Même PDF, servi par un lien signé temporaire : c'est ainsi que l'app
     * mobile l'ouvre (elle ne peut pas joindre son jeton à un navigateur).
     * Sans session, sans `settings()` : la société vient de la facture.
     */
    public function signedPdf(Invoice $invoice){
        return $this->pdfResponse($invoice);
    }

    /** Écritures SYSCOHADA d'un relevé (CSV). */
    public function InvoiceJournal($merchant_id,$invoice_id){
        $this->ownsOrAbort($merchant_id);
        $invoice = $this->repo->invoiceGet($merchant_id,$invoice_id);
        abort_if(blank($invoice), 404, __('statement.not_found'));

        return $this->csvResponse(SyscohadaJournal::csv([$invoice]), 'journal-syscohada-'.$invoice->invoice_id.'.csv');
    }

    /** Écritures SYSCOHADA de tous les relevés de la société sur une période (administration). */
    public function JournalPeriod(Request $request){
        $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date']]);
        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->to)->endOfDay() : now()->endOfMonth();

        // Chaque écriture dans la période de SA date (S73) : ventes et
        // compensation à l'émission du relevé, reversement à la date de l'ordre
        // de virement, recharges et remises à la leur. La société est celle du
        // compte connecté — on est dans une requête.
        return $this->csvResponse(
            SyscohadaJournal::csvLignes(SyscohadaJournal::periode((int) settings()->id, $from, $to)),
            'journal-syscohada-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv'
        );
    }

    /** Le PDF officiel — `SettlementPdf`, le même que la pièce jointe du courriel (S75, R9). */
    private function pdfResponse(Invoice $invoice){
        return response(SettlementPdf::render($invoice), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.SettlementPdf::fileName($invoice).'"',
        ]);
    }

    private function csvResponse(string $content, string $filename){
        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function InvoiceCSV($merchant_id,$invoice_id){
        $this->ownsOrAbort($merchant_id);
       
        if($this->repo->invoiceGet($merchant_id,$invoice_id)):
            $invoice = $this->repo->invoiceGet($merchant_id,$invoice_id);
            $invoiceParcels = InvoiceParcel::where('invoice_id',$invoice->id)->get();  
            return Excel::download(new InvoiceExport($invoiceParcels,$invoice),'invoice-'.$invoice->merchant->business_name.'-'.$invoice->invoice_date.'.xlsx');
        else:
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        endif; 
    }
 
    public function InvoiceGenerateMenuallyIndex(){
        return view('backend.setting.invoice_generate.index');
    }

    public function InvoiceGenerateMenually(){
        try {
            // La société est passée explicitement : sans elle, la commande
            // traiterait TOUTES les sociétés, et un administrateur déclencherait
            // la génération des relevés des autres transporteurs. Ici on est
            // dans une requête, donc `settings()` résout la bonne société.
            Artisan::call('invoice:generate', ['--societe' => settings()->id]);
            Toastr::success(__('invoice.invoice_generated_successfully'),__('message.success'));
            return redirect()->back();
        } catch (\Throwable $th) {
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }


    public function PaidInvoice(Request $request){
        $invoices            = $this->repo->getPaidInvoices();
        $processInvoices     = $this->repo->getProcessInvoices();
        $unpaidInvoices      = $this->repo->getUnpaidInvoices();
        return view('backend.invoice.paid_invoice_list',compact('invoices','processInvoices','unpaidInvoices','request'));
    }
}
