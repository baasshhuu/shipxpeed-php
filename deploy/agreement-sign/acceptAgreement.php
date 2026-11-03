    public function acceptAgreement(Request $request) // aadhaar-sign-v2
    {
        /** @var SellerList $seller */
        $seller = auth('seller')->user();

        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Seller not authenticated.'], 401);
        }

        // The agreement is signed with the Aadhaar verified via DigiLocker (stored server-side)
        $cf = \App\Http\Controllers\selleradmin\CashfreeController::class;
        $aadhaar = method_exists($cf, 'sessionKey') ? session($cf::sessionKey('aadhaar')) : null;
        if (!$aadhaar || empty($aadhaar['name'])) {
            return response()->json(['success' => false, 'message' => 'Please verify your Aadhaar with DigiLocker before signing.'], 422);
        }

        if (!$request->boolean('agree')) {
            return response()->json(['success' => false, 'message' => 'You must agree to the terms before signing.'], 422);
        }

        $clientName = $aadhaar['name'];
        $clientAddress = $aadhaar['address'] ?: ($request->clientAddress ?? '');
        $signedAt = now();
        $signature = [
            'method'          => 'Aadhaar (DigiLocker) via Cashfree Secure ID',
            'name'            => $clientName,
            'uid_masked'      => $aadhaar['uid_masked'] ?? '',
            'dob'             => $aadhaar['dob'] ?? '',
            'verification_id' => $aadhaar['verification_id'] ?? '',
            'reference_id'    => $aadhaar['reference_id'] ?? '',
            'ip'              => $request->ip(),
            'user_agent'      => substr((string) $request->userAgent(), 0, 200),
            'signed_at'       => $signedAt->format('d/m/Y H:i:s') . ' IST',
        ];

        try {
            $pdf = PDF::loadView('seller.agreement-pdf', [
                'seller'        => $seller,
                'clientName'    => $clientName,
                'clientAddress' => $clientAddress,
                'clientPAN'     => $request->clientPAN,
                'currentDate'   => $signedAt->format('d/m/Y'),
                'signature'     => $signature,
            ]);

            $path = 'agreements/agreement' . $seller->id . '.pdf';
            Storage::put('public/' . $path, $pdf->output());
        } catch (\Throwable $e) {
            Log::error('Agreement PDF failed', ['seller_id' => $seller->id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Could not generate the agreement PDF: ' . \Illuminate\Support\Str::limit($e->getMessage(), 220)], 500);
        }

        try {
            $row = [
                'client_name'    => $clientName,
                'client_address' => $clientAddress,
                'client_pan'     => $request->clientPAN,
                'pdf_path'       => $path,
            ];
            if (\Illuminate\Support\Facades\Schema::hasColumn('seller_agreements', 'agreement_content')) {
                $row['agreement_content'] = json_encode(['signature' => $signature], JSON_UNESCAPED_UNICODE);
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('seller_agreements', 'agreement_accepted_at')) {
                $row['agreement_accepted_at'] = $signedAt;
            }
            $agreement = SellerAgreement::firstOrNew(['seller_id' => $seller->id]);
            $agreement->forceFill($row)->save();

            SellerList::where('id', $seller->id)->update(['agreement_accepted' => 1]);

            $addr = SellerAddress::firstOrNew(['seller_id' => $seller->id]);
            $addr->address_line = $clientAddress;
            $addr->save();
        } catch (\Throwable $e) {
            Log::error('Agreement save failed', ['seller_id' => $seller->id, 'error' => $e->getMessage(), 'at' => $e->getFile() . ':' . $e->getLine()]);
            return response()->json(['success' => false, 'message' => 'Could not save the signed agreement: ' . \Illuminate\Support\Str::limit($e->getMessage(), 220)], 500);
        }

        $downloadUrl = \Illuminate\Support\Facades\Route::has('seller.agreement.download') ? route('seller.agreement.download') : null;

        return response()->json([
            'success'      => true,
            'message'      => 'Agreement signed with Aadhaar successfully. Please complete your KYC.',
            'download_url' => $downloadUrl,
        ]);
    }
