<?php
namespace App\Repositories\FrontWeb\Service;
interface ServiceInterface {
    public function get(); 
    public function getAll(); 
    public function latest_services();
    public function getTakeService();
    public function getFind($id);
    public function store($request);
    public function update($id,$request);
    public function delete($id);
}