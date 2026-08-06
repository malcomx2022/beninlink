<?php
namespace App\Repositories\FrontWeb\Partner;
interface PartnerInterface {
    public function get(); 
    public function getAll();
    public function getFind($id);
    public function store($request);
    public function update($id,$request);
    public function delete($id);
}