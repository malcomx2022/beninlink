<?php

namespace App\Http\Controllers;

use App\Http\Requests\Installer\InstallRequest;
use App\Models\Backend\GeneralSettings;
use App\Models\CustomerDomain;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use GeoSot\EnvEditor\Facades\EnvEditor;
use Illuminate\Support\Facades\Http;

class InstallerController extends Controller
{
    public function index(){
        return view('installer.index');
    }

  
    public function installing(InstallRequest $request){

       
     
       ini_set('max_execution_time', 900); //900 seconds
       $host           = $request->host;
       $dbuser         = $request->dbuser;
       $dbpassword     = $request->dbpassword;
       $dbname         = $request->dbname;
       $first_name     = $request->first_name;
       $last_name      = $request->last_name;
       $user_name      = $first_name.' '.$last_name;
       $email          = $request->email;
       $login_password = $request->password ? $request->password : "";
 
        //purchase code verification
        $purchaseVerify = $this->PurchaseVerification($request->purchase_code); 
        if($purchaseVerify != 200):
             return redirect()->back()->withErrors(['purchase_code'=> $purchaseVerify])->withInput($request->all());
         endif; 
         //end purchase code verification 
 
        // check for valid database connection
        try {
             $mysqli = @new \mysqli($host, $dbuser, $dbpassword, $dbname);
        } catch (\Throwable $th) {
            try {
                    //  set database details
                    $this->envWrite('DB_HOST', $host);
                    $this->envWrite('DB_DATABASE', $dbname);
                    $this->envWrite('DB_USERNAME', $dbuser);
                    $this->envWrite('DB_PASSWORD', $dbpassword);
                    $this->envWrite('APP_INSTALLED', ''); 
                    Artisan::call('config:clear');
                    DB::connection()->getPdo();
            } catch (\Throwable $th) {   
                return redirect()->back()->withErrors(['invalid_db'=>'The database information is Invalid.']);
            }
        }
        if (isset($mysqli) && mysqli_connect_errno()) {
           return redirect()->back()->with('error', __('Please enter valid database information.'))->withInput($request->all());
        }
        if(isset($mysqli)):
            $mysqli->close();
        endif;

        //check for valid email
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
           return redirect()->back()->with('error', __('Please enter a valid email address.'))->withInput($request->all());
        }
 
        //  set database details
        $this->envWrite('DB_HOST', $host);
        $this->envWrite('DB_DATABASE', $dbname);
        $this->envWrite('DB_USERNAME', $dbuser);
        $this->envWrite('DB_PASSWORD', $dbpassword);
        $this->envWrite('APP_INSTALLED', '');
        Artisan::call('key:generate');
        Artisan::call('config:clear');
        
        $data = [
            'user_name'     => $user_name,
            'email'         => $email,
            'login_password'=> $login_password,
            'purchase_code' => $request->purchase_code
        ];
        return redirect()->route('final',$data);
   }
   public function finish(Request $request){
            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
                foreach(DB::select('SHOW TABLES') as $table) {
                    $table_array = get_object_vars($table);
                    Schema::drop($table_array[key($table_array)]);
            }
            Artisan::call('migrate:refresh');
            Artisan::call('db:seed'); 
            $user                = User::find(1);
            $user->name          = $request->user_name;
            $user->email         = $request->email;
            $user->password      = bcrypt($request->login_password);
            $user->save();

            $generalSettings = GeneralSettings::where('id',1)->update(['purchase_code'=>$request->purchase_code]);
 
            $this->envWrite('APP_INSTALLED', 'yes');
            $this->envWrite('APP_URL', scheme_name(get_host()));
            Artisan::call('config:clear'); 
            return redirect('/');
        }
        //env write
        private function envWrite($key,$value)
        {
            if (EnvEditor::keyExists($key)) {
                EnvEditor::editKey($key, '"' . trim($value) . '"');
            } else {
                EnvEditor::addKey($key, '"' . trim($value) . '"');
            }
        }
 
        public function PurchaseVerification($code) { 
			return 200;
        } 


 
 
        public function customerInstallation(Request $request){
            try {
                if($request->domain): 
                    $customerDomainfind = CustomerDomain::where(['domain'=>$request->domain,'purchase_code'=>$request->purchase_code])->first();
                    if($customerDomainfind):
                        $customerDomainfind->domain        = $request->domain;
                        $customerDomainfind->purchase_code = $request->purchase_code; 
                        $customerDomainfind->save();
                    else:
                        CustomerDomain::create([
                            'domain'       => $request->domain, 
                            'purchase_code'=>$request->purchase_code
                        ]);
                        return true;
                    endif;
                endif;
                return false; 
            } catch (\Throwable $th) {
                return false;
            }
        }
}
