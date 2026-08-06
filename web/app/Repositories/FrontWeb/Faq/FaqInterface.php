<?php
namespace App\Repositories\FrontWeb\Faq;
interface FaqInterface {
    public function get(); 
    public function getActive(); 
    public function getFind($id);
    public function store($request);
    public function update($id,$request);
    public function delete($id);
}