<?php
namespace App\Repositories\BankTransaction;

interface BankTransactionInterface {

    public function all();
    public function filter($request);
    public function filterSearch($request);

}
