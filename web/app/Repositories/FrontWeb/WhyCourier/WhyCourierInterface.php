<?php
namespace App\Repositories\FrontWeb\WhyCourier;
interface WhyCourierInterface {
    public function get(); 
    public function getAll();
    public function getFind($id);
    public function store($request);
    public function update($id,$request);
    public function delete($id);
}