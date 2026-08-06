<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;

class LocalizationController extends Controller
{
    public function setLocalization($language){
        App::setLocale($language);
        session()->put('locale', $language);
        return redirect()->back();
    }
}
