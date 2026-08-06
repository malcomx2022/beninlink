<?php
namespace App\Repositories\Hub;

interface HubInterface {

    public function all();
    public function hubs();
    public function get($id);
    public function filter($request);
    public function store($request);
    public function update($id, $request);
    public function delete($id);
    public function parcelFilter($request,$id);
}
