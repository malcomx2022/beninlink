<?php

namespace App\Http\Controllers\Backend;

use Brian2694\Toastr\Facades\Toastr;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Backend\Addon;
use Illuminate\Support\Str;
use ZipArchive;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AddonController extends Controller
{
    public function __construct() {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (env('DEMO')) {
            Toastr::error(__('addon.error_msg_demo'),__('message.error'));
            return back();
        }
        $addons = Addon::query()->orderBy('name', 'asc')->get();
        return view('backend.addons.index', compact('addons'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('backend.addons.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        Cache::forget('addons');

        if (env('DEMO')) {
            Toastr::error(__('addon.error_msg_demo'),__('message.error'));
            return back();
        }

        if (extension_loaded('zip')) {
            if ($request->hasFile('addon_zip')) {
                // Create update directory.
                $dir = 'addons';
                if (!is_dir($dir))
                    mkdir($dir, 0777, true);

                $path = Storage::disk('local')->put('addons', $request->addon_zip);

                $zipped_file_name = $request->addon_zip->getClientOriginalName();

                //Unzip uploaded update file and remove zip file.
                $zip = new ZipArchive;
                $res = $zip->open(base_path('public/' . $path));

                $random_dir = Str::random(10);

                $dir = trim($zip->getNameIndex(0), 'assets/');
                if ($res === true) {
                    $res = $zip->extractTo(base_path('temp/' . $random_dir . '/addons'));
                    $zip->close();
                } else {
                    dd('could not open');
                }

                $str = file_get_contents(base_path('temp/' . $random_dir . '/addons/' . $dir . '/config.json'));
                $json = json_decode($str, true);
                if (settings()->current_version >= $json['minimum_item_version']) {
                    if (count(Addon::where('unique_identifier', $json['unique_identifier'])->get()) == 0) {
                        $addon = new Addon;
                        $addon->name = $json['name'];
                        $addon->unique_identifier = $json['unique_identifier'];
                        $addon->version = $json['version'];
                        $addon->activated = 1;
                        $addon->image = $json['addon_banner'];
                        $addon->purchase_code = $request->purchase_code;
                        $addon->save();

                        // Create new directories.
                        if (!empty($json['directory'])) {
                            foreach ($json['directory'][0]['name'] as $directory) {
                                if (is_dir(base_path($directory)) == false) {
                                    mkdir(base_path($directory), 0777, true);

                                } else {
                                    echo "error on creating directory";
                                }

                            }
                        }

                        // Create/Replace new files.
                        if (!empty($json['files'])) {
                            foreach ($json['files'] as $file) {
                                copy(base_path('temp/' . $random_dir . '/' . $file['root_directory']), base_path($file['update_directory']));
                            }

                        }

                        // Run sql modifications
                        $sql_path = base_path('temp/' . $random_dir . '/addons/' . $dir . '/sql/update.sql');
                        if (file_exists($sql_path)) {
                            DB::unprepared(file_get_contents($sql_path));
                        }
                        Toastr::success(__('addon.update_msg'),__('message.success'));
                        return back();
                    }
                } else {
                    Toastr::error(__('addon.error_msg_install'),__('message.error'));
                    return back();
                }
            }
        }
        else {
            Toastr::error(__('addon.error_msg_install_extension'),__('message.error'));
            return back();
        }
        Toastr::error(__('addon.error_msg_install_extension'),__('message.error'));
        return back();
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Addon $addon
     * @return \Illuminate\Http\Response
     */
    public function edit(Addon $addon)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Addon $addon
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Addon $addon
     * @return \Illuminate\Http\Response
     */
    public function activation(Request $request)
    {
        if (env('DEMO')) {
            Toastr::error(__('addon.error_msg_demo'),__('message.error'));
            return 0;
        }
        $addon = Addon::find($request->id);
        $addon->activated = $request->status;
        $addon->save();

        Cache::forget('addons');

        return 1;
    }
}
