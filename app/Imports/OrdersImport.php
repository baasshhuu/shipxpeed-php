<?php

namespace App\Imports;

use App\Models\Order;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Http; // for HTTP request
use Illuminate\Support\Facades\Route;

  use App\Http\Controllers\selleradmin\ShipmentController;
use Illuminate\Http\Request;
class OrdersImport implements ToCollection
{



  

public function collection(Collection $rows)
{
    $rows->shift(); // Remove header row

    foreach ($rows as $row) {
        try {
            // Prepare request-like array
            $requestData = new Request([
                'order_number'      => $row[0],
                'payment_type'      => $row[1],
                'collectable_amount'=> $row[2],
                'order_items'       => [
                    [
                        'name'     => $row[3],
                        'sku'      => $row[4],
                        'qty' => $row[5],
                        'price'    => $row[6],
                    ]
                ],
                'consignee'         => [
                    'name'       => $row[7],
                    'phone'      => $row[8],
                    'email'      => $row[9],
                    'address'    => $row[10],
                    'address_2'  => $row[11],
                    'pincode'    => $row[12],
                    'city'       => $row[13],
                    'state'      => $row[14],
                ],
                'pickup'            => [
                    'warehouse_name' => $row[15],
                    'name'           => $row[16],
                    'address'        => $row[17],
                    'address_2'      => $row[18],
                    'pincode'        => $row[19],
                    'city'           => $row[20],
                    'state'          => $row[21],
                    'phone'          => $row[22],
                ],
                'package_weight'    => $row[23],
                'package_length'    => $row[24],
                'package_breadth'   => $row[25],
                'package_height'    => $row[26],
            ]);

            // Directly call controller method
            app(ShipmentController::class)->createShipmentbulk($requestData);

        } catch (\Exception $e) {
            \Log::error('Shipment creation exception: ' . $e->getMessage());
            continue;
        }
    }
}



}
