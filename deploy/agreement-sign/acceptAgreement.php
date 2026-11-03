    public function acceptAgreement(Request $request)
    {
        /** @var SellerList $seller */
        $seller = auth('seller')->user();

        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Seller not authenticated.'], 401);
        }

        // The agreement is signed with the Aadhaar verified via DigiLocker (stored server-side)
        $aadhaar = session(\App\Http\Controllers\selleradmin\CashfreeController::sessionKey('aadhaar'));
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
            return response()->json(['success' => false, 'message' => 'Could not generate the agreement PDF. Please try again.'], 500);
        }

        SellerAgreement::updateOrCreate(
            ['seller_id' => $seller->id],
            [
                'client_name'       => $clientName,
                'client_address'    => $clientAddress,
                'client_pan'        => $request->clientPAN,
                'agreement_content' => json_encode(['signature' => $signature], JSON_UNESCAPED_UNICODE),
                'pdf_path'          => $path,
            ]
        );

        SellerList::where('id', $seller->id)->update(['agreement_accepted' => 1]);

        SellerAddress::updateOrCreate(
            ['seller_id' => $seller->id],
            ['address_line' => $clientAddress]
        );

        return response()->json([
            'success'      => true,
            'message'      => 'Agreement signed with Aadhaar successfully. Please complete your KYC.',
            'download_url' => route('seller.agreement.download'),
        ]);
    }
