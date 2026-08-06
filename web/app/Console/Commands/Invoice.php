<?php

namespace App\Console\Commands;

use App\Enums\ParcelStatus;
use App\Models\Backend\Merchant;
use App\Models\Backend\Merchantpanel\Invoice as InvoiceModel;
use App\Models\Backend\Parcel;
use App\Repositories\Invoice\InvoiceInterface;
use App\Repositories\Merchant\MerchantInterface;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class Invoice extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoice:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Merchant Schedule Invoice generate';

    /**
     * Execute the console command.
     *
     * @return int
     */

    protected $invoiceRepo,$merchantRepo;
    public function __construct(InvoiceInterface $invoiceRepo, MerchantInterface $merchantRepo)
    {
        parent::__construct();
        $this->invoiceRepo  = $invoiceRepo;
        $this->merchantRepo = $merchantRepo;
    }

    public function handle()
    {
        //auto merchant invoice generate
        foreach ($this->merchantRepo->merchantIdlist() as $merchant) {
            $this->invoiceRepo->store($merchant->id);
        }

    }


}
