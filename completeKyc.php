    public function completeKyc(Request $request)
    {
        $authSeller = Auth::guard('seller')->user();
        if (!$authSeller) {
            return redirect()->back()->with('error', 'Unauthorized or seller not found.');
        }

        $seller = SellerList::find($authSeller->id);
        if (!$seller) {
            return redirect()->back()->with('error', 'Seller not found in database.');
        }

        if ((int) $seller->agreement_accepted !== 1) {
            return redirect()->back()->with('error', 'Please sign the Client Service Agreement with Aadhaar first.');
        }

        // Only values verified by Cashfree on the server are trusted (see CashfreeController)
        $key = fn ($w) => \App\Http\Controllers\selleradmin\CashfreeController::sessionKey($w);
        $pan  = session($key('pan'));
        $gst  = session($key('gst'));
        $bank = session($key('bank'));

        if (!$pan) {
            return redirect()->back()->with('error', 'Please verify your PAN.');
        }
        if (!$bank) {
            return redirect()->back()->with('error', 'Please verify your bank account.');
        }

        try {
            SellerBankDetail::updateOrCreate(
                ['seller_id' => $seller->id],
                [
                    'account_number'      => $bank['bank_account'],
                    'account_holder_name' => $bank['name_at_bank'],
                    'ifsc_code'           => $bank['ifsc'],
                ]
            );

            $seller->update([
                'bank_account_verified' => $bank['bank_account'],
                'bank_name'             => $bank['name_at_bank'],
                'account_number'        => $bank['bank_account'],
                'ifsc_code'             => $bank['ifsc'],
                'pan_verified_name'     => $pan['registered_name'],
                'pan_number'            => $pan['pan'],
                'gst_verified_name'     => $gst['legal_name_of_business'] ?? null,
                'gst_no'                => $gst['gstin'] ?? null,
                'status'                => 1,
                'kyc_status'            => 1,
            ]);

            session()->forget([$key('pan'), $key('gst'), $key('bank')]);

            return redirect()->back()->with('success', 'KYC completed successfully.');
        } catch (\Exception $e) {
            \Log::error('KYC Error: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Something went wrong while completing KYC.');
        }
    }
