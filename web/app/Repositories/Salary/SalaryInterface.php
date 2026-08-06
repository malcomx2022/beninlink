<?php
namespace App\Repositories\Salary;

interface SalaryInterface {
    public function autogenerate($request);
    public function salaries();
    public function salaryGenerateDelete($id);
    public function salaryGenerateStore($request);
    public function salaryGenerateUpdate($request);
    public function singleSalaryGenerate($id);
    public function all();
    public function salaryFilter($request);
    public function get($id);
    public function monthSalary($salary);
    public function store($request);
    public function edit($id);
    public function update($id,$request);
    public function delete($id);




}
