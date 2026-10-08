<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\AutoGenerateRequest;
use App\Http\Requests\Payroll\StoreRequest;
use App\Models\Backend\Payroll\SalaryGenerate;
use App\Models\Subscribe;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Repositories\Salary\SalaryInterface;
use Brian2694\Toastr\Facades\Toastr;
class SalaryGenerateController extends Controller
{

    protected $repo;
    public function __construct(SalaryInterface $repo)
    {
        $this->repo  = $repo;
    }

    public function index(){

        $salaries   = $this->repo->salaries();
        return view('backend.payroll.salary_generate',compact('salaries'));
    }

    public function salaryAutoGenerate(AutoGenerateRequest $request){
        if($this->repo->autogenerate($request)):
            Toastr::success(__('Salaries generated successfully.'),__('message.success'));
            return redirect()->route('salary.generate.index');
        else:
            Toastr::error(__('Something went wrong.'),__('message.error'));
            return redirect()->back();
        endif;
    }

    public function salaryGenerateDelete($id){
        if($this->repo->salaryGenerateDelete($id)):
            Toastr::success(__('Generated salaries deleted.'),__('message.success'));
            return redirect()->route('salary.generate.index');
        else:
            Toastr::error(__('Something went wrong.'),__('message.error'));
            return redirect()->back();
        endif;
    }

    public function create(){
        return view('backend.payroll.create');
    }

    public function store(StoreRequest $request){
        // S64 — la pre-verification precede le depot (garde depuis S48), et elle
        // interrogeait la paie d'un utilisateur d'en face : un ORACLE d'existence
        // sur les bulletins d'une autre societe, avant meme le refus d'ecriture.
        $user  = User::companywise()->find($request->user_id);
        $salaryGenerated            = SalaryGenerate::companywise()->where('user_id',$request->user_id)->where('month',$request->month)->first();
        if($salaryGenerated):
            Toastr::error(__('Salaries already generated.'),__('message.error'));
            return redirect()->back();
        endif;
        if($this->repo->salaryGenerateStore($request)):
            Toastr::success(__('Salary created successfully.'),__('message.success'));
            return redirect()->route('salary.generate.index');
        else:
            Toastr::error(__('Something went wrong.'),__('message.error'));
            return redirect()->back();
        endif;
    }


    public function edit($id){
        $singleSalary  = $this->repo->singleSalaryGenerate($id);
        return view('backend.payroll.edit',compact('singleSalary'));
    }

    public function update(StoreRequest $request){

        if($this->repo->salaryGenerateUpdate($request)):
            Toastr::success(__('Salary updated successfully.'),__('message.success'));
            return redirect()->route('salary.generate.index');
        else:
            Toastr::error(__('Something went wrong.'),__('message.error'));
            return redirect()->back();
        endif;
    }

    public function subscribe(){
        $subscribes   = Subscribe::companywise()->paginate(15);
        return view('backend.subscribe',compact('subscribes'));
    }
}
