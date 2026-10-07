<?php

namespace App\Http\Controllers\admin;

use App\Helper\Helper;
use App\Library\Database;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Artisan;
use App\Models\Setting as SettingModels;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SettingController extends Controller
{
    public function __construct()
    {
        ini_set('memory_limit', '512M');
        $this->middleware('auth');
    }

    public function setting(Request $request, $id = null): View|RedirectResponse
    {
        if ($request->method() == 'GET' && Helper::userCan(101, 'can_view')) {
            $setting = SettingModels::where('setting_type', $id)->active()->get();
            if ($setting->count()) {
                $data = array(
                    'title'         => "Application Setting",
                    'type'          => empty(config('constant.setting_array')[$id]) ? "" : config('constant.setting_array')[$id],
                    'setting_id'    => $id,
                    'setting'       => $setting
                );

                return view('setting.index', $data);
            } else {
                return to_route('dashboard')->withError('No Settings Found..!!', 'Error');
            }
        }

        if ($request->method() == 'POST' && Helper::userCan(101, 'can_edit')) {
            if ($id == 1) {
                $request->validate([
                    'application_name'  => 'required|max:100',
                    'copyright'         => 'required|max:100',
                    'address'           => 'required|max:100',
                    'email'             => 'required|max:100',
                    'phone'             => 'required|max:100',
                    'favicon'           => 'image|mimes:jpg,png,jpeg,gif,svg|max:240',
                    'logo'              => 'image|mimes:jpg,png,jpeg,gif,svg|max:1024',
                ]);
            }

            if ($id == 2) {
                $request->validate([
                    'facebook'      => 'required|max:100',
                    'twitter'       => 'required|max:100',
                    'linkdin'       => 'required|max:100',
                    'instagram'     => 'required|max:100',
                ]);
            }

            if ($id == 3) {
                $request->validate([
                    'email_from'    => 'required|max:100',
                    'smtp_host'     => 'required|max:100',
                    'smtp_port'     => 'required|max:100',
                    'smtp_user'     => 'required|max:100',
                    'smtp_pass'     => 'required|max:100',
                ]);
            }

            if ($id == 4) {
                $request->validate([
                    'razorpay_key'    => 'required|max:100',
                    'razorpay_secret' => 'required|max:100',
                    'merchant_id'     => 'required|max:100',
                ]);
            }

            if ($id == 5) {
                $request->validate([
                    'textlocal_key'     => 'required|max:100',
                    'textlocal_url'     => 'required|max:100',
                    'textlocal_hash'    => 'required|max:100',
                    'textlocal_sender'  => 'required|max:100',
                ]);
            }

            if ($id == 6) {
                $request->validate([
                    'notify_modal_show'    => 'boolean',
                    'notify_modal_content' => 'required|max:10000',
                    'load_money_qr_code'   => 'image|mimes:jpg,png,jpeg,gif,svg|max:1024',
                ]);
            }

            if ($id == 7) {
                $request->validate([
                    'information_banner'            => 'image|mimes:jpg,png,jpeg,gif,svg|max:1024',
                    "force_update_android"          => 'boolean',
                    "force_update_ios"              => 'boolean',
                    "app_version_android"           => 'required|max:100',
                    "app_version_ios"               => 'required|max:100',
                    "app_url_android"               => 'required|max:100',
                    "app_url_ios"                   => 'required|max:100',
                    "force_update_message_android"  => 'required|max:100',
                    "force_update_message_ios"      => 'required|max:100',
                    "maintenance"                   => 'required|max:100',
                    "maintenance_toggle"            => 'boolean',
                    "information_banner_toggle"     => 'boolean',
                ]);
            }

            $data = $request->except(['site_settings', 'permission', '_token']);
            if ($request->file('favicon')) {
                Helper::deleteFile($request->site_settings['favicon']);
                $data['favicon'] = Helper::saveFile($request->file('favicon'), 'application');
            }

            if ($request->file('logo')) {
                Helper::deleteFile($request->site_settings['logo']);
                $data['logo'] = Helper::saveFile($request->file('logo'), 'application');
            }

            if ($request->file('load_money_qr_code')) {
                Helper::deleteFile($request->site_settings['load_money_qr_code']);
                $data['load_money_qr_code'] = Helper::saveFile($request->file('load_money_qr_code'), 'application');
            }

            if ($request->file('information_banner')) {
                Helper::deleteFile($request->site_settings['information_banner']);
                $data['information_banner'] = Helper::saveFile($request->file('information_banner'), 'application');
            }

            if ($id == 4) $data['is_commision']       = $request->boolean('is_commision');
            if ($id == 6) $data['notify_modal_show']  = $request->boolean('notify_modal_show');
            if ($id == 7) {
                $data['force_update_android']         = $request->boolean('force_update_android');
                $data['force_update_ios']             = $request->boolean('force_update_android');
                $data['maintenance_toggle']           = $request->boolean('maintenance_toggle');
                $data['information_banner_toggle']    = $request->boolean('information_banner_toggle');
            }

            foreach ($data as $key => $input) {
                SettingModels::where('setting_name', $key)->update(['filed_value' => $input]);
            }

            return to_route('setting', ['id' => $id])->withSuccess('Setting Updated Successfully..!!');
        }

        return to_route('dashboard')->withError('Not Allowed..!!');
    }

    public function database_backup(): BinaryFileResponse
    {
        $path = Database::backup();
        return response()->download($path)->deleteFileAfterSend(true);
    }

    public function serverControl(): View
    {
        Artisan::call('about', ['--json' => true]);
        $appData =   json_decode(trim(Artisan::output()), true);

        $diskTotal      = round(disk_total_space(storage_path()) / (1024 * 1024 * 1024), 2);
        $diskFree       = round(disk_free_space(storage_path()) / (1024 * 1024 * 1024), 2);
        $diskUsed       = round($diskTotal - $diskFree, 2);

        return view('setting.server', compact('appData', 'diskTotal', 'diskFree', 'diskUsed'));
    }

    public function serverControlSave(Request $request): RedirectResponse
    {
        try {
            switch ($request->input('type')) {
                case 1:
                    Artisan::call('config:cache');
                    break;
                case 2:
                    Artisan::call('route:cache');
                    break;
                case 3:
                    Artisan::call('view:cache');
                    break;
                case 4:
                    Artisan::call('config:clear');
                    break;
                case 5:
                    Artisan::call('route:clear');
                    break;
                case 6:
                    Artisan::call('view:clear');
                    break;
                case 7:
                    Artisan::call('event:cache');
                    break;
                case 8:
                    Artisan::call('event:clear');
                    break;
                case 9:
                    Artisan::call('cache:clear');
                    break;
                case 10:
                    Artisan::call('optimize');
                    break;
                case 11:
                    Artisan::call('optimize:clear');
                    break;
                default:
                    Artisan::call('optimize');
                    break;
            }

            return to_route('server-control')->withSuccess(trim(Artisan::output()));
        } catch (\Throwable $th) {
            // dd($th->getMessage(), Artisan::output());
            return to_route('server-control')->withError($th->getMessage());
        }
    }
}
