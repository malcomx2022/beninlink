<?php
namespace App\Repositories\Income;

interface IncomeInterface {

    public function all();
    public function accountHeads();
    public function hubCheck($request);
    public function hubUsers($id);
    public function hubUserAccounts($request);
    public function filter($request);
    public function get($id);
    public function store($data);
    public function update($id, $data);
    public function delete($id);
    public function file($image_id, $data);

}
