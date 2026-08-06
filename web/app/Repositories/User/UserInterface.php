<?php
namespace App\Repositories\User;

interface UserInterface {

    public function all();
    public function hubs();
    public function departments();
    public function designations();
    public function get($id);
    public function filter($request);
    public function store($data);
    public function update($id, $data);
    public function delete($id);
    public function file($image_id, $data);
    public function permissionUpdate($id,$request);

}
