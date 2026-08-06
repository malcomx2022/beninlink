<?php
namespace App\Repositories\Account;

interface AccountInterface {

    public function all();

    public function getAll();
    public function get($request);
    public function filter($request);
    public function store($request);
    public function update($id, $request);
    public function delete($id);
    public function users();
    public function currentBalance($data);
    public function useraccount($id);
}
