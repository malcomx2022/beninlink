<?php
namespace App\Repositories\Profile;

interface ProfileInterface {

    public function get($id);
    public function update($id, $request);
    public function updatePassword($id, $request);
    public function file($image_id, $data);
}
