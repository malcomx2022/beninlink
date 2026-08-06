<?php
namespace App\Repositories\Superadmin\Plan;
interface PlanInterface {
    public function get();
    public function getActive();
    public function getFind($id);
    public function store($request);
    public function update($id,$request);
    public function delete($id); 
}