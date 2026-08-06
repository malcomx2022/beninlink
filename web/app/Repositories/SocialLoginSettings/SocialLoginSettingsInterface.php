<?php
namespace App\Repositories\SocialLoginSettings;

interface SocialLoginSettingsInterface {
    public function update($request,$social);
}
