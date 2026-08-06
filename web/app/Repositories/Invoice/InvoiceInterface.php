<?php

namespace App\Repositories\Invoice;

interface InvoiceInterface {
    //merchant panel invoice
    public function get();
    public function invoiceGet($merchant_id,$invoice_id);
    public function store($merchant_id);
    public function InvoiceDetails($invoiceId);
    //admin panel merchant invoice
    public function merchantInvoiceGet($merchantId);
    public function merchantInvoiceDetails($merchantId,$invoiceId);
    public function statusUpdate($request,$merchant_id);
    //both panel invoice print method
    public function InvoicePdf($merchant_id,$invoice_id);
    public function getPaidInvoices();
    public function getProcessInvoices();
    public function getUnpaidInvoices();
    public function getFind($id);
    public function invoiceLists();

}
