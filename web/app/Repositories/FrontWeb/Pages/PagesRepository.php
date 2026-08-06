<?php

namespace App\Repositories\FrontWeb\Pages;

use App\Models\Backend\FrontWeb\Page;
use App\Repositories\FrontWeb\Pages\PagesInterface;
use Illuminate\Support\Facades\Auth;

class PagesRepository implements PagesInterface
{

    public function all()
    {
        return Page::companyWise()->paginate(10);
    }

    public function get($page)
    {
        return Page::companyWise()->where('page', $page)->active()->first();
    }

    public function getFind($id)
    {
        return Page::companyWise()->findOrFail($id);
    }

    public function update($id, $request)
    {
        try {
            $page              = $this->getFind($id);
            $page->company_id     = settings()->id;
            $page->title       = $request->title;
            $page->description = $request->description;
            $page->status      = $request->status;
            $page->save();
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }
}
