<?php

namespace App\Repositories\FrontWeb\SocialLink;

use App\Models\Backend\FrontWeb\SocialLink;
use App\Repositories\FrontWeb\SocialLink\SocialLinkInterface;

class SocialLinkRepository implements SocialLinkInterface
{
    public function get()
    {
        return SocialLink::companyWise()->orderBy('position', 'asc')->paginate(10);
    }

    public function getAll()
    {
        return SocialLink::companyWise()->active()->orderBy('position', 'asc')->get();
    }

    public function getFind($id)
    {
        return SocialLink::companyWise()->findOrFail($id);
    }

    public function store($request)
    {
        try {
            $socialLink                 = new SocialLink();
            $socialLink->company_id     = settings()->id;
            $socialLink->name           = $request->name;
            $socialLink->icon           = $request->icon;
            $socialLink->link           = $request->link;
            $socialLink->position       = $request->position;
            $socialLink->status         = $request->status;
            $socialLink->save();
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function update($id, $request)
    {
        try {
            $socialLink                 = $this->getFind($id);
            $socialLink->company_id     = settings()->id;
            $socialLink->name           = $request->name;
            $socialLink->icon           = $request->icon;
            $socialLink->link           = $request->link;
            $socialLink->position       = $request->position;
            $socialLink->status         = $request->status;
            $socialLink->save();
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function delete($id)
    {
        return SocialLink::destroy($id);
    }
}
