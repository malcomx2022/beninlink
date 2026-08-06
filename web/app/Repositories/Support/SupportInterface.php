<?php
namespace App\Repositories\Support;

interface SupportInterface {

    public function all();
    public function departments();
    public function get($id);
    public function chats($id);
    public function store($request);
    public function reply($request);
    public function update($id, $request);
    public function delete($id);
    public function file ($image_id, $data);
    public function statusUpdate($id,$request);

}
