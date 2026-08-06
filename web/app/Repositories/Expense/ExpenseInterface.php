<?php
namespace App\Repositories\Expense;

interface ExpenseInterface {

    public function all();
    public function get($id);
    public function filter($request);
    public function store($data);
    public function update($id, $data);
    public function delete($id);
    public function file($image_id, $data);
}
