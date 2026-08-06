<?php
namespace App\Repositories\FrontWeb\Pages;
interface PagesInterface {
    public function all(); 
    public function get($page); 
    public function getFind($id); 
    public function update($id,$request); 
}