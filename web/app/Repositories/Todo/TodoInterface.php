<?php
namespace App\Repositories\Todo;

interface TodoInterface {

    public function all();
    public function get($id);
    public function store($request);
    public function update($request);
    public function delete($id);
    public function todoProcessing($id,$request);
    public function todoComplete($id,$request);
}
