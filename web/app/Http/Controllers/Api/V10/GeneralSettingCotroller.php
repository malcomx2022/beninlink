<?php

namespace App\Http\Controllers\Api\V10;

use App\Http\Controllers\Controller;
use App\Repositories\Currency\CurrencyInterface;
use App\Repositories\GeneralSettings\GeneralSettingsInterface;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;

class GeneralSettingCotroller extends Controller
{
    use ApiReturnFormatTrait;
    protected $repo,$currencies;
    public function __construct(GeneralSettingsInterface $repo, CurrencyInterface $currencies)
    {
        $this->repo   = $repo;
        $this->currencies = $currencies;
    }
    public function index(){
        $generalSettings = $this->repo->all();
        return $this->responseWithSuccess('General settings information.',$generalSettings,200);
    }

    public function currencies(){
        $currencies = $this->currencies->getActive();
        return $this->responseWithSuccess('All Currency.',$currencies,200);
    }
}
