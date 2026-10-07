<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CourierMasterController extends Controller
{
    public function index()
    {
        $couriers = \DB::table('couriers')->orderBy('name')->get();
        $accounts = \DB::table('courier_accounts as ca')
            ->join('couriers as c', 'c.id', '=', 'ca.courier_id')
            ->select('ca.*', 'c.name as master_courier_name')
            ->orderByDesc('ca.id')
            ->get();

        $slabsByAccount = \DB::table('courier_slabs')
            ->where('status', 1)
            ->get()
            ->groupBy('courier_account_id');

        return view('courier-master.index', compact('couriers', 'accounts', 'slabsByAccount'));
    }

    private function generateUniqueCode($subCourierName)
    {
        $base = Str::slug($subCourierName, '_');
        do {
            $code = $base . '_' . rand(100, 999);
            $exists = \DB::table('courier_accounts')->where('code', $code)->exists();
        } while ($exists);
        return $code;
    }

    public function store(Request $request)
    {
        $request->validate([
            'master_courier_id' => 'nullable|integer',
            'new_master_courier_name' => 'nullable|string|max:255',
            'sub_courier_name' => 'required|string|max:255',
            'mode_type' => 'required|in:Air,Surface',
            'load_type' => 'required|in:B2B,B2C',
            'logo' => 'nullable|image|max:2048',
            'slabs' => 'nullable|array',
            'slabs.*' => 'nullable|string|max:255',
        ]);

        \DB::beginTransaction();
        try {
            if ($request->master_courier_id) {
                $courierId = $request->master_courier_id;
            } else {
                if (!$request->new_master_courier_name) {
                    return back()->withErrors(['master_courier_id' => 'Please select or enter a master courier name.'])->withInput();
                }
                $existing = \DB::table('couriers')->where('name', $request->new_master_courier_name)->first();
                if ($existing) {
                    $courierId = $existing->id;
                } else {
                    $courierId = \DB::table('couriers')->insertGetId([
                        'name' => $request->new_master_courier_name,
                        'code' => Str::slug($request->new_master_courier_name, '_'),
                        'status' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $logoPath = null;
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('courier-logos', 'public');
            }

            $code = $this->generateUniqueCode($request->sub_courier_name);

            $accountId = \DB::table('courier_accounts')->insertGetId([
                'courier_id' => $courierId,
                'code' => $code,
                'name' => $request->sub_courier_name,
                'logo' => $logoPath,
                'courier_type' => $request->load_type,
                'mode_type' => $request->mode_type,
                'is_default' => 0,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($request->slabs) {
                foreach ($request->slabs as $slabName) {
                    if (trim($slabName ?? '') === '') continue;
                    \DB::table('courier_slabs')->insert([
                        'courier_account_id' => $accountId,
                        'name' => $slabName,
                        'status' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            \DB::commit();
            return redirect()->route('courier.master.index')->with('success', 'Courier "' . $request->sub_courier_name . '" created successfully with code: ' . $code);
        } catch (\Throwable $e) {
            \DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'sub_courier_name' => 'required|string|max:255',
            'mode_type' => 'required|in:Air,Surface',
            'load_type' => 'required|in:B2B,B2C',
            'logo' => 'nullable|image|max:2048',
            'new_slabs' => 'nullable|array',
        ]);

        \DB::beginTransaction();
        try {
            $account = \DB::table('courier_accounts')->where('id', $id)->first();
            if (!$account) {
                return back()->withErrors(['error' => 'Courier account not found.']);
            }

            $updateData = [
                'name' => $request->sub_courier_name,
                'courier_type' => $request->load_type,
                'mode_type' => $request->mode_type,
                'updated_at' => now(),
            ];

            if ($request->hasFile('logo')) {
                $updateData['logo'] = $request->file('logo')->store('courier-logos', 'public');
            }

            \DB::table('courier_accounts')->where('id', $id)->update($updateData);

            if ($request->new_slabs) {
                foreach ($request->new_slabs as $slabName) {
                    if (trim($slabName ?? '') === '') continue;
                    \DB::table('courier_slabs')->insert([
                        'courier_account_id' => $id,
                        'name' => $slabName,
                        'status' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            \DB::commit();
            return redirect()->route('courier.master.index')->with('success', 'Courier updated successfully.');
        } catch (\Throwable $e) {
            \DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }
}
