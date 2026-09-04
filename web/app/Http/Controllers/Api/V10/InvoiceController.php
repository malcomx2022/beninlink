<?php

namespace App\Http\Controllers\Api\V10;

use App\Http\Controllers\Controller;
use App\Http\Resources\v10\InvoiceDetailsResource;
use App\Http\Resources\v10\InvoiceResource;
use App\Repositories\Invoice\InvoiceInterface;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class InvoiceController extends Controller
{
    use ApiReturnFormatTrait;

    protected $repo;
    public $data = [];
     public function __construct(InvoiceInterface $repo)
     {
        $this->repo    = $repo;
     }
     
    //invoice list
    public function invoiceLists() { 
        $invoice_list = $this->repo->invoiceLists(); 
        $invoice = InvoiceResource::collection($invoice_list);
        return $invoice;
    }
    public function invoiceDetails($invoice_id){   
        
        $invoice = $this->repo->getFind($invoice_id);
        // Le repository ne rend que les factures du marchand connecte : hors de
        // son perimetre, on repond 404 plutot que de laisser la Resource
        // exploser sur un modele absent.
        abort_if(blank($invoice), 404, __('invoice.not_found'));

        return new InvoiceDetailsResource($invoice);
    }

    /**
     * Lien de téléchargement du relevé en PDF, signé et valable 15 minutes.
     *
     * L'app ne peut pas joindre son jeton Bearer à un navigateur : elle
     * demande ici un lien signé, puis l'ouvre. Le périmètre est celui de
     * `getFind()` — le marchand connecté, et lui seul.
     */
    public function pdfLink($invoice_id){
        $invoice = $this->repo->getFind($invoice_id);
        abort_if(blank($invoice), 404, __('statement.not_found'));

        $expires = now()->addMinutes(15);

        return $this->responseWithSuccess(__('statement.link_ready'), [
            'url' => URL::temporarySignedRoute('invoice.statement.pdf', $expires, ['invoice' => $invoice->id]),
            'expires_at' => $expires->toIso8601String(),
        ], 200);
    }
}
