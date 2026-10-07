<?php

namespace App\Imports;

use App\Models\ZonePriceSetting;
use App\Models\LogisticProvider;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ZonePriceImport implements ToModel, WithHeadingRow, WithValidation
{
    protected $sellerId;

    public function __construct($sellerId)
    {
        $this->sellerId = $sellerId;
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Static Logistic Providers Array (same as in controller)
        $LogisticProviders = [
         // Delhivery
"Delhivery",
"Delhivery_Air",
// Delhivery
// Shadowfax
"Shadowfax",
// Shadowfax
// selloship
"Ekart2KG_selloship",
// selloship
// DTDC
"DTDC_Surface_500gm",
"DTDC_Surface_1kg",
"DTDC_Air",
// DTDC
// tekipost
'tekipost_Delhivery_5kg', 
'tekipost_Delhivery_10kg', 
'tekipost_Delhivery_1_KG', 
'tekipost_Ekart_2_KG_Fixed', 
'tekipost_Amazon_2_kg', 
'tekipost_Amazon_500_GM',
// tekipost
// parcelx
"Parcel_X_Delhivery",
"Parcel_X_Amazon",
"Parcel_X_Amazon_1KG",
"Parcel_X_Amazon_2KG",
// parcelx
// Shiprocket
"Shiprocket_Xpressbee",
"Shiprocket_Delhivery",
"Shiprocket_Bluedart",
"shiprocket_Bluedart_1kg",
// Shiprocket
"Ekart500gm",
'Ekart500gm_boxd'

        ];

        // Check if LogisticProvider exists in our static array
        $logisticProviderName = $row['logistic_provider'];
        
        if (!in_array($logisticProviderName, $LogisticProviders)) {
            throw new \Exception('Logistic Provider not found: ' . $logisticProviderName);
        }

        // Check if record exists, update or create
        return ZonePriceSetting::updateOrCreate(
            [
                'seller_id' => $this->sellerId,
                'zone' => $row['zone'],
                'LogisticProvider' => $logisticProviderName, // Store the name directly
            ],
            [
                'cod_price' => $row['cod_price'] ?? 0,
                'cod_fix_price' => $row['cod_fix_price'] ?? 0,
                'prepaid_price' => $row['prepaid_price'] ?? 0,
                'prepaid_fix_price' => $row['prepaid_fix_price'] ?? 0,
                'cod_charge_parsent' => $row['cod_charge_parsent'] ?? 0,
                'RTO_Credit' => $row['rto_credit'] ?? 0,
                'status' => 1,
            ]
        );
    }

    public function rules(): array
    {
        return [
            'zone' => 'required|string',
            'logistic_provider' => 'required',
            'cod_price' => 'numeric|min:0',
            'cod_fix_price' => 'numeric|min:0',
            'prepaid_price' => 'numeric|min:0',
            'prepaid_fix_price' => 'numeric|min:0',
            'cod_charge_parsent' => 'numeric|min:0|max:100',
            'rto_credit' => 'numeric|min:0',
        ];
    }
}