<?php
namespace App\Repositories\MerchantPanel\Support;

interface SupportInterface {

    public function all();
    public function departments();
    public function get($id);
    public function store($request);
    public function reply($request);
    public function update($id, $request);
    public function delete($id);
    public function file ($image_id, $data);
    public function chats($id);

}
