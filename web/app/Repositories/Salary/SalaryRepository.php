<?php
namespace App\Repositories\Salary;

use App\Enums\AccountHeads;
use App\Enums\UserType;
use App\Models\Backend\Account;
use App\Models\Backend\BankTransaction;

use App\Models\Backend\Salary;
use App\Models\User;
use App\Models\Backend\Payroll\SalaryGenerate;
use App\Repositories\Salary\SalaryInterface;
use Carbon\Carbon;

class SalaryRepository  implements SalaryInterface
{
    public function salaries(){
        return SalaryGenerate::companywise()->orderBy('id','desc')->paginate(10);
    }

    public function monthSalary($salary){
        return SalaryGenerate::companywise()->where(['user_id'=>$salary->user_id,'month'=>$salary->month])->first();
    }
    public function salaryFilter($request){
        $salary  = SalaryGenerate::companywise()->with('user')->where(function($query)use($request){
            if($request->month){
                $query->where('month',$request->month);
            }
            if($request->user_id):
                $query->where('user_id',$request->user_id);
            endif;
        })->orderBy('id','desc')->paginate(10);
        return $salary;
    }

    public function autogenerate($request){
        try {

          $users   = User::companywise()->whereIn('user_type',[UserType::ADMIN,UserType::DELIVERYMAN])->get();
          foreach ($users as  $user) {
                $salaryGenerated            = SalaryGenerate::companywise()->where('user_id',$user->id)->where('month',$request->month)->first();
                if(!$salaryGenerated):
                    $salaryGenerate             = new SalaryGenerate();
                    $salaryGenerate->company_id   = settings()->id;
                    $salaryGenerate->user_id    = $user->id;
                    $salaryGenerate->month      = $request->month;
                    $salaryGenerate->amount     = $user->salary ? $user->salary:0;
                    $salaryGenerate->note       = 'Auto Generated';
                    $salaryGenerate->save();
                endif;
          }
          return true;
        } catch (\Throwable $th) {
           return false;
        }
    }

    public function salaryGenerateStore($request){
        try {
            $user  = User::find($request->user_id);
            $salaryGenerated            = SalaryGenerate::companywise()->where('user_id',$request->user_id)->where('month',$request->month)->first();
            if(!$salaryGenerated):
                $salaryGenerate             = new SalaryGenerate();
                $salaryGenerate->company_id = settings()->id;
                $salaryGenerate->user_id    = $request->user_id;
                $salaryGenerate->month      = $request->month;
                $salaryGenerate->amount     = $request->amount;
                $salaryGenerate->note       = $request->note;
                $salaryGenerate->save();
            endif;
            return true;
        } catch (\Throwable $th) {

             return false;
        }
    }

    public function singleSalaryGenerate($id){
        return SalaryGenerate::find($id);
    }


    public function salaryGenerateUpdate($request){
        try{
            $user  = User::find($request->user_id);
            $salaryGenerate             = SalaryGenerate::find($request->id);
            $salaryGenerate->user_id    = $request->user_id;
            $salaryGenerate->month      = $request->month;
            $salaryGenerate->amount     = $request->amount;
            $salaryGenerate->note       = $request->note;
            $salaryGenerate->save();
            return true;
          } catch (\Throwable $th) {

             return false;
          }
    }


    public function salaryGenerateDelete($id){
        try {
            $salary             =  SalaryGenerate::find($id); 
            if($salary->company_id == settings()->id):
                $salary->delete();
                return true;
            endif;
            return false;

        } catch (\Throwable $th) {
            return false;
        }
    }

    //end salary generate
    public function all(){
        return Salary::companywise()->orderBy('id','desc')->paginate(10);
    }
    public function get($id){
        return Salary::find($id);
    }
    public function store($request){
        try {
            $salary  = new Salary();
            $salary->company_id      = settings()->id;
            $salary->user_id      = $request->user_id;
            $salary->account_id   = $request->account_id;
            $salary->month        = $request->month;
            $salary->date         = $request->date;
            $salary->amount       = $request->amount;
            $salary->note         = $request->note;
            $salary->save();
            $account            = Account::find($request->account_id);
            $account->balance   = ($account->balance - $request->amount);
            $account->save();
            $transaction                       = new BankTransaction();
            $transaction->company_id           = settings()->id;
            $transaction->account_id           = $request->account_id;
            $transaction->type                 = AccountHeads::EXPENSE;
            $transaction->amount               = $request->amount;
            $transaction->date                 = $request->date;
            $transaction->note                 = __('salary.user_salary_expense');
            $transaction->save();
            return $salary;
        } catch (\Throwable $th) {
           return false;
        }
    }
    public function edit($id){
        return Salary::find($id);
    }
    public function update($id,$request){
        try {
            $salary  = Salary::find($id);
            //income
            $transaction                       = new BankTransaction();
            $transaction->company_id           = settings()->id;
            $transaction->account_id           = $salary->account_id;
            $transaction->type                 = AccountHeads::INCOME;
            $transaction->amount               = $salary->amount;
            $transaction->date                 = $salary->date;
            $transaction->note                 = __('salary.user_salary_expense');
            $transaction->save();
            $account            = Account::find($salary->account_id);
            $account->balance   = ($account->balance + $salary->amount);
            $account->save();
            //income
            $salary->user_id      = $request->user_id;
            $salary->account_id   = $request->account_id;
            $salary->month        = $request->month;
            $salary->date         = $request->date;
            $salary->amount       = $request->amount;
            $salary->note         = $request->note;
            $salary->save();

            $account            = Account::find($request->account_id);
            $account->balance   = ($account->balance - $request->amount);
            $account->save();

            $transaction                       = new BankTransaction();
            $transaction->company_id           = settings()->id;
            $transaction->account_id           = $request->account_id;
            $transaction->type                 = AccountHeads::EXPENSE;
            $transaction->amount               = $request->amount;
            $transaction->date                 = $request->date;
            $transaction->note                 = __('salary.user_salary_expense');
            $transaction->save();
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }
    public function delete($id){
        try {
            $salary             =  Salary::find($id);
            $account            = Account::find($salary->account_id);
            $account->balance   = ($account->balance + $salary->amount);
            $account->save();
            $transaction                       = new BankTransaction();
            $transaction->company_id           = settings()->id;
            $transaction->account_id           = $salary->account_id;
            $transaction->type                 = AccountHeads::INCOME;
            $transaction->amount               = $salary->amount;
            $transaction->date                 = $salary->date;
            $transaction->note                 = __('salary.user_salary_income');
            $transaction->save();
            $salary->delete();
            return true;

        } catch (\Throwable $th) {
            return false;
        }

    }
}
