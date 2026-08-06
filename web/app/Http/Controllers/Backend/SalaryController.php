<?php

namespace App\Http\Controllers\Backend;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Salary\StoreRequest;
use App\Http\Requests\Salary\UpdateRequest;
use App\Models\Backend\Salary;
use App\Models\User;
use App\Models\Backend\Payroll\SalaryGenerate;
use App\Repositories\Account\AccountInterface;
use App\Repositories\Salary\SalaryInterface;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
class SalaryController extends Controller
{
    protected $repo;
    public function __construct(SalaryInterface $repo,AccountInterface $accounts)
    {
        $this->repo      = $repo;
        $this->accounts  = $accounts;
    }
    public function index(Request $request){
        $salaries  = $this->repo->all();
        return view('backend.salary.index',compact('salaries','request'));
    }

    public function salaryFilter(Request $request){
        $salaries  = $this->repo->salaryFilter($request);
        return view('backend.salary.index',compact('salaries','request'));
    }

    public function create(){
        $accounts    = $this->accounts->all();
        return view('backend.salary.create',compact('accounts'));
    }

    public function store(StoreRequest $request){

        if((double) $request->amount > (double) $request->account_balance){
            Toastr::warning(__('salary.not_enough_balance'),__('message.warning'));
            return back()->withInput();
        }
        $salary = $this->repo->store($request);
        if($salary):
            Toastr::success('Salary successfully paid.',__('message.success'));
            return redirect()->route('salary.index');
        else:
            Toastr::error('Something went wrong.',__('message.error'));
            return redirect()->back();
        endif;
    }
    public function edit($id){
        $singleSalary   = $this->repo->edit($id);
        $accounts       = $this->accounts->all();
        return view('backend.salary.edit',compact('singleSalary','accounts'));
    }
    public function update(UpdateRequest $request){
        $salary    = Salary::find($request->id);
        $account  = $this->accounts->get($salary->account_id);
        $total_balance = $account->balance+$salary->amount;
         if((double) $total_balance < (double) $request->amount):
            Toastr::warning(__('salary.not_enough_balance'),__('message.warning'));
            return back()->withInput();
         endif;
        if($this->repo->update($request->id,$request)):
            Toastr::success('Salary successfully updated paid.',__('message.success'));
            return redirect()->route('salary.index');
        else:
            Toastr::error('Something went wrong.',__('message.error'));
            return redirect()->back();
        endif;
    }
    public function delete($id){

        if($this->repo->delete($id)):
            Toastr::success('Salary successfully deleted.',__('message.success'));
            return redirect()->route('salary.index');
        else:
            Toastr::error('Something went wrong.',__('message.error'));
            return redirect()->back();
        endif;
    }

    public function salaryGet(Request $request){
        $salaryAmount = SalaryGenerate::where('user_id',$request->user_id)->where('month',$request->month)->first();

        if($salaryAmount):
           $salary  = $salaryAmount->amount;
        else:
           $salary  = 0;
        endif;
        return $salary;
    }

    public function paySlip($id){
        $salary      = $this->repo->get($id);
        $month_salary=$this->repo->monthSalary($salary);

        return view('backend.salary.pay_slip',compact('salary','month_salary'));
    }

    public function Users(Request $request){
        if($request->ajax()):
            $users = User::companywise()->where('name','like','%'.$request->search.'%')->whereNot('user_type',UserType::MERCHANT)->paginate(10);
            $response = [];
            foreach ($users as  $user) {
                $response [] = [
                    'id'  => $user->id,
                    'text'=> $user->name
                ];
            }
            return response()->json($response);
        endif;
    }
}
