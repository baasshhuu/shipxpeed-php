<?php

namespace App\Services;

/**
 * Single source for the seller API documentation: the docs pages (/seller/api, /api-docs),
 * their code samples and the Postman collection are all generated from endpoints().
 * Keep it in sync with routes/api.php and the Shipxpeedapi controllers.
 */
class SellerApiDocs
{
    public function endpoints(): array
    {
        return [
            [
                'id' => 'auth',
                'group' => 'Getting started',
                'title' => 'Generate token',
                'method' => 'POST',
                'path' => '/token-generate',
                'auth' => false,
                'summary' => 'Exchange your seller panel email and password for an API token. Send the token in the Authorization header of every other call.',
                'notes' => [
                    'The same token is returned on every call, so several systems can use one account. Rotate it any time from Seller panel → API documentation → Regenerate.',
                    'Limited to 10 attempts per minute per IP.',
                ],
                'fields' => [
                    ['email', 'string', true, 'Seller panel login email.'],
                    ['password', 'string', true, 'Seller panel password.'],
                ],
                'request' => ['email' => 'you@business.com', 'password' => 'your-panel-password'],
                'response' => ['success' => true, 'token' => 'sxp_Q2x7mP0aZk4rT9vB1nW6yE3uH8dL5c', 'token_type' => 'Bearer'],
                'errors' => [
                    [401, ['success' => false, 'message' => 'Invalid credentials']],
                    [422, ['success' => false, 'message' => 'Validation errors', 'errors' => ['email' => ['The email field must be a valid email address.']]]],
                ],
            ],
            [
                'id' => 'serviceability',
                'group' => 'Shipping',
                'title' => 'Fetch couriers & rates',
                'method' => 'POST',
                'path' => '/serviceability',
                'auth' => true,
                'summary' => 'Returns every courier that serves the lane, with your price, the COD charge, zone and expected delivery date. Pick one and pass its courier_id to Create shipment.',
                'notes' => [
                    'Prices follow your account\'s rate card and change with weight, zone and payment type, so fetch them again when the parcel changes.',
                    'bookable is false for couriers you can see but not yet book through the API. Book those from the panel.',
                    '/api/serviceability/all-providers is the same call under its older name.',
                ],
                'fields' => [
                    ['origin_pincode', 'integer', true, 'Pickup pincode (6 digits).'],
                    ['destination_pincode', 'integer', true, 'Delivery pincode (6 digits).'],
                    ['pickup_state', 'string', true, 'Pickup state, e.g. Delhi.'],
                    ['destination_state', 'string', true, 'Delivery state, e.g. Maharashtra.'],
                    ['payment_type', 'string', true, 'cod or prepaid.'],
                    ['order_amount', 'number', true, 'Order value in ₹. For COD this is the amount to collect.'],
                    ['package_weight', 'number', true, 'Dead weight in grams.'],
                    ['package_length', 'number', false, 'Length in cm. Default 10.'],
                    ['package_breadth', 'number', false, 'Breadth in cm. Default 8.'],
                    ['package_height', 'number', false, 'Height in cm. Default 5.'],
                ],
                'request' => [
                    'origin_pincode' => 110053, 'destination_pincode' => 400001,
                    'pickup_state' => 'Delhi', 'destination_state' => 'Maharashtra',
                    'payment_type' => 'cod', 'order_amount' => 1299,
                    'package_weight' => 500, 'package_length' => 20, 'package_breadth' => 15, 'package_height' => 8,
                ],
                'response' => [
                    'success' => true,
                    'message' => 'Successfully fetched serviceability from all providers!',
                    'responseCode' => 200,
                    'total_providers' => 3,
                    'data' => [
                        ['courier_id' => 'delhivery', 'bookable' => true, 'courierName' => 'Delhivery', 'courierCharge' => 96.76, 'freightCharges' => 62.0, 'codCharge' => 20.0, 'zone' => 'D', 'chargeable_weight' => 500, 'provider_name' => 'ShipXpeed'],
                        ['courier_id' => 'sx1_14', 'bookable' => true, 'courierName' => 'Xpressbees Surface', 'courierCharge' => 89.5, 'freightCharges' => 58.0, 'codCharge' => 18.0, 'zone' => 'D', 'estimated_delivery_date' => '2026-10-31', 'chargeable_weight' => 500, 'provider_name' => 'ShipXpeed'],
                        ['courier_id' => 'sx2_7', 'bookable' => true, 'courierName' => 'Ekart Surface', 'courierCharge' => 84.0, 'freightCharges' => 54.0, 'codCharge' => 17.0, 'zone' => 'D', 'estimated_delivery_date' => '2026-11-01', 'chargeable_weight' => 500, 'provider_name' => 'ShipXpeed'],
                    ],
                ],
                'response_fields' => [
                    ['courier_id', 'string|null', 'Pass this to Create shipment to book with this courier.'],
                    ['bookable', 'boolean', 'true when the courier can be booked through the API.'],
                    ['courierName', 'string', 'Courier and service, e.g. Delhivery, Xpressbees Surface.'],
                    ['courierCharge', 'number', 'Total charge in ₹ for this shipment.'],
                    ['freightCharges', 'number', 'Freight part of the charge in ₹.'],
                    ['codCharge', 'number', 'COD part of the charge in ₹ (0 for prepaid).'],
                    ['zone', 'string', 'Shipping zone of the lane.'],
                    ['estimated_delivery_date', 'string', 'Expected delivery date (YYYY-MM-DD), when the courier gives one.'],
                    ['chargeable_weight', 'number', 'Weight the courier bills in grams.'],
                ],
                'errors' => [
                    [200, ['success' => false, 'message' => 'Validation failed', 'errors' => ['payment_type' => ['The selected payment type is invalid.']], 'responseCode' => 422]],
                ],
            ],
            [
                'id' => 'create-shipment',
                'group' => 'Shipping',
                'title' => 'Create shipment',
                'method' => 'POST',
                'path' => '/create-shipment',
                'auth' => true,
                'summary' => 'Creates the order and, when you pass a courier_id from Fetch couriers, books it with that courier in the same call and returns the AWB.',
                'notes' => [
                    'Leave courier_id out to only create the order. You can then ship it from the panel (Orders → Ship now).',
                    'Your wallet must cover the shipment charge and keep at least ₹150, unless negative balance is enabled for your account. The account must be active (shown as Active in the panel).',
                    'Send pincodes and phone numbers as strings. Weight is in grams, dimensions in cm.',
                    'Your order_number is saved as customer_order_id and can be searched in the panel. Shipxpeed also assigns its own order_number (SPX#…).',
                ],
                'fields' => [
                    ['order_number', 'string', false, 'Your own order reference, e.g. #5588.'],
                    ['payment_type', 'string', true, 'cod or prepaid.'],
                    ['collectable_amount', 'number', true, 'Amount the courier collects on delivery. Use 0 for prepaid.'],
                    ['courier_id', 'string', false, 'courier_id from Fetch couriers. Books the shipment right away.'],
                    ['consignee.name', 'string', true, 'Customer name.'],
                    ['consignee.phone', 'string', true, '10-digit mobile number.'],
                    ['consignee.email', 'string', false, 'Customer email.'],
                    ['consignee.address', 'string', true, 'House / street.'],
                    ['consignee.address_2', 'string', false, 'Area / landmark.'],
                    ['consignee.pincode', 'string', true, '6-digit pincode.'],
                    ['consignee.city', 'string', true, 'City.'],
                    ['consignee.state', 'string', true, 'State.'],
                    ['order_items[].name', 'string', true, 'Product name.'],
                    ['order_items[].sku', 'string', true, 'SKU.'],
                    ['order_items[].qty', 'integer', true, 'Quantity (1 or more).'],
                    ['order_items[].price', 'number', true, 'Unit price in ₹.'],
                    ['pickup.warehouse_name', 'string', true, 'Your warehouse / pickup point name.'],
                    ['pickup.name', 'string', true, 'Contact person at pickup.'],
                    ['pickup.phone', 'string', true, 'Pickup contact number.'],
                    ['pickup.address', 'string', true, 'Pickup address.'],
                    ['pickup.address_2', 'string', false, 'Pickup address line 2.'],
                    ['pickup.pincode', 'string', true, 'Pickup pincode.'],
                    ['pickup.city', 'string', true, 'Pickup city.'],
                    ['pickup.state', 'string', true, 'Pickup state.'],
                    ['rto.*', 'object', false, 'Return address with the same fields as pickup. Defaults to the pickup address.'],
                    ['package_weight', 'number', true, 'Dead weight in grams.'],
                    ['package_length', 'number', true, 'Length in cm.'],
                    ['package_breadth', 'number', true, 'Breadth in cm.'],
                    ['package_height', 'number', true, 'Height in cm.'],
                ],
                'request' => [
                    'order_number' => '#5588',
                    'payment_type' => 'cod',
                    'collectable_amount' => 1299,
                    'courier_id' => 'sx1_14',
                    'consignee' => [
                        'name' => 'Priya Sharma', 'phone' => '9876543210', 'email' => 'priya@example.com',
                        'address' => 'Flat 12, Sea View Apartments', 'address_2' => 'Colaba Causeway',
                        'pincode' => '400001', 'city' => 'Mumbai', 'state' => 'Maharashtra',
                    ],
                    'order_items' => [
                        ['name' => 'Cotton Kurta - Blue, M', 'sku' => 'KRT-BLU-M', 'qty' => 1, 'price' => 1299],
                    ],
                    'pickup' => [
                        'warehouse_name' => 'Ghonda Warehouse', 'name' => 'Rahul Verma', 'phone' => '9811122233',
                        'address' => 'U-152/3, Gali No. 6, Arvind Nagar', 'address_2' => 'Ghonda',
                        'pincode' => '110053', 'city' => 'Delhi', 'state' => 'Delhi',
                    ],
                    'package_weight' => 500, 'package_length' => 20, 'package_breadth' => 15, 'package_height' => 8,
                ],
                'response' => [
                    'success' => true,
                    'message' => 'Shipment created successfully and assigned to Xpressbees Surface',
                    'responseCode' => 200,
                    'data' => [
                        'order_id' => 48213,
                        'order_number' => 'SPX#48213',
                        'customer_order_id' => '#5588',
                        'awb_number' => '14344940012345',
                        'courier_assigned' => true,
                        'courier_id' => 'sx1_14',
                        'courier_name' => 'Xpressbees Surface',
                        'payment_type' => 'cod',
                        'order_amount' => 1299,
                        'collectable_amount' => 1299,
                        'package_weight' => 500,
                        'created_at' => '2026-10-26 18:42:10',
                        'assignment_result' => ['status' => true, 'success' => true, 'message' => 'Courier assigned successfully via Xpressbees Surface'],
                        'serviceability_results' => ['…same items as Fetch couriers…'],
                    ],
                ],
                'response_fields' => [
                    ['order_id', 'integer', 'Shipxpeed order ID.'],
                    ['order_number', 'string', 'Shipxpeed order number (SPX#…).'],
                    ['customer_order_id', 'string', 'Your order_number, as sent.'],
                    ['awb_number', 'string|null', 'AWB / tracking number. null when the order was not booked.'],
                    ['courier_assigned', 'boolean', 'true when the courier booking succeeded.'],
                    ['assignment_result', 'object', 'Booking result from the courier, including the reason when it fails.'],
                ],
                'errors' => [
                    [401, ['success' => false, 'message' => 'Unauthorized or inactive seller']],
                    [422, ['success' => false, 'message' => 'Validation failed', 'errors' => ['consignee.pincode' => ['The consignee.pincode field is required.']], 'responseCode' => 422]],
                    [200, ['success' => true, 'message' => 'Shipment created successfully but courier assignment failed', 'data' => ['order_id' => 48214, 'awb_number' => null, 'courier_assigned' => false, 'assignment_result' => ['status' => false, 'success' => false, 'message' => 'Insufficient wallet balance. Please recharge your wallet.']]]],
                ],
            ],
            [
                'id' => 'warehouse',
                'group' => 'Shipping',
                'title' => 'Create warehouse',
                'method' => 'POST',
                'path' => '/create-warehouse',
                'auth' => true,
                'summary' => 'Saves a pickup address to your account. It also becomes the return address. Warehouses appear in the panel under Settings → Warehouses.',
                'notes' => [],
                'fields' => [
                    ['name', 'string', true, 'Warehouse name (max 150 characters).'],
                    ['phone', 'string', true, 'Contact number, 10 to 15 digits.'],
                    ['address', 'string', true, 'Address line 1.'],
                    ['address_two', 'string', false, 'Address line 2.'],
                    ['pincode', 'string', true, '6-digit pincode.'],
                    ['city', 'string', true, 'City.'],
                    ['state', 'string', true, 'State.'],
                    ['country', 'string', true, 'Country, e.g. India.'],
                ],
                'request' => [
                    'name' => 'Ghonda Warehouse', 'phone' => '9811122233',
                    'address' => 'U-152/3, Gali No. 6, Arvind Nagar', 'address_two' => 'Ghonda',
                    'pincode' => '110053', 'city' => 'Delhi', 'state' => 'Delhi', 'country' => 'India',
                ],
                'response' => [
                    'success' => true,
                    'message' => 'Warehouse created successfully!',
                    'data' => ['id' => 312, 'seller_id' => 57, 'name' => 'Ghonda Warehouse', 'phone' => '9811122233', 'pincode' => '110053', 'city' => 'Delhi', 'state' => 'Delhi', 'country' => 'India', 'address_line1' => 'U-152/3, Gali No. 6, Arvind Nagar', 'address_line2' => 'Ghonda'],
                ],
                'errors' => [
                    [422, ['success' => false, 'message' => 'Validation errors', 'errors' => ['pincode' => ['Pincode must be 6 digits']]]],
                ],
            ],
            [
                'id' => 'label',
                'group' => 'After booking',
                'title' => 'Download label',
                'method' => 'POST',
                'path' => '/label-by-awb',
                'auth' => true,
                'summary' => 'Returns a link to the shipping label of one of your booked shipments.',
                'notes' => [],
                'fields' => [
                    ['awb_number', 'string', true, 'AWB of the shipment.'],
                ],
                'request' => ['awb_number' => '14344940012345'],
                'response' => [
                    'success' => true,
                    'message' => 'label generated successfully',
                    'data' => ['seller_id' => 57, 'order_id' => 48213, 'order_number' => 'SPX#48213', 'awb_number' => '14344940012345', 'courier' => 'Xpressbees Surface', 'label_url' => 'https://shipxpeed.tech/storage/labels/label_57_48213.pdf'],
                ],
                'errors' => [
                    [404, ['success' => false, 'message' => 'Order not found with this AWB number']],
                ],
            ],
            [
                'id' => 'track',
                'group' => 'After booking',
                'title' => 'Track shipment',
                'method' => 'POST',
                'path' => '/track-shipment',
                'auth' => true,
                'summary' => 'Current status and scan history of one of your shipments, newest scan first.',
                'notes' => ['Statuses are refreshed from the courier through the day. Use webhooks to get updates pushed to you.'],
                'fields' => [
                    ['awb', 'string', true, 'AWB of the shipment.'],
                ],
                'request' => ['awb' => '14344940012345'],
                'response' => [
                    'status' => true,
                    'data' => [
                        'awb_number' => '14344940012345',
                        'order_id' => 48213,
                        'order_number' => 'SPX#48213',
                        'customer_order_id' => '#5588',
                        'courier_name' => 'Xpressbees Surface',
                        'current_status' => 'in_transit',
                        'payment_type' => 'cod',
                        'collectable_amount' => 1299,
                        'shipped_on' => '2026-10-26 18:42:10',
                        'delivered_on' => null,
                        'destination' => ['city' => 'Mumbai', 'state' => 'Maharashtra', 'pincode' => '400001'],
                        'created_at' => '2026-10-26 18:42:10',
                        'tracking_history' => [
                            ['status' => 'In Transit', 'scan_type' => 'IT', 'location' => 'Bhiwandi Hub', 'remarks' => 'Shipment arrived at hub', 'courier' => 'Xpressbees', 'time' => '2026-10-28 06:15:00'],
                            ['status' => 'Picked Up', 'scan_type' => 'PU', 'location' => 'Delhi', 'remarks' => 'Shipment picked up', 'courier' => 'Xpressbees', 'time' => '2026-10-27 14:02:00'],
                        ],
                    ],
                ],
                'errors' => [
                    [404, ['status' => false, 'message' => 'Order not found for AWB: 14344940012345']],
                ],
            ],
            [
                'id' => 'cancel',
                'group' => 'After booking',
                'title' => 'Cancel shipment',
                'method' => 'POST',
                'path' => '/cancel-shipment',
                'auth' => true,
                'summary' => 'Cancels one of your shipments with the courier before it is picked up.',
                'notes' => ['Couriers usually refuse cancellation once the parcel is picked up.'],
                'fields' => [
                    ['awb', 'string', true, 'AWB of the shipment.'],
                ],
                'request' => ['awb' => '14344940012345'],
                'response' => ['success' => true, 'message' => 'Shipment cancelled successfully', 'awb' => '14344940012345', 'data' => []],
                'errors' => [
                    [404, ['success' => false, 'message' => 'Order not found with AWB: 14344940012345', 'awb' => '14344940012345']],
                    [400, ['success' => false, 'message' => 'Order is already cancelled', 'awb' => '14344940012345', 'current_status' => 'cancelled']],
                ],
            ],
            [
                'id' => 'ndr',
                'group' => 'After booking',
                'title' => 'NDR action',
                'method' => 'POST',
                'path' => '/ndr-action',
                'auth' => true,
                'summary' => 'Act on a failed delivery: ask for a re-attempt, update the address or phone, or return the parcel.',
                'notes' => ['Available on couriers that accept NDR instructions by API. Others return status false with a message; handle those from the panel\'s NDR page.'],
                'fields' => [
                    ['awb_number', 'string', true, 'AWB of the shipment.'],
                    ['action', 'string', false, 're-attempt (default), change-address, change-phone or rto.'],
                    ['reattempt_date', 'string', false, 'Preferred re-attempt date, YYYY-MM-DD.'],
                    ['address', 'string', false, 'New address (change-address).'],
                    ['phone', 'string', false, 'New phone number (change-phone).'],
                    ['name', 'string', false, 'Updated customer name.'],
                    ['landmark', 'string', false, 'Landmark to help the delivery agent.'],
                    ['remark', 'string', false, 'Note for the courier.'],
                ],
                'request' => ['awb_number' => '14344940012345', 'action' => 're-attempt', 'reattempt_date' => '2026-10-30', 'remark' => 'Customer available after 6 pm'],
                'response' => ['status' => true, 'message' => 'NDR action sent'],
                'errors' => [
                    [200, ['status' => false, 'message' => 'Order not found for this AWB number or you do not have access to this order.']],
                ],
            ],
        ];
    }

    /** courier_id => what it books. Shipclues / Fship IDs are listed separately as patterns. */
    public function courierIds(): array
    {
        return [
            ['delhivery', 'Delhivery Surface'],
            ['delhivery_air', 'Delhivery Express (Air)'],
            ['delhivery_5kg', 'Delhivery 5 kg slab'],
            ['delhivery_10kg', 'Delhivery 10 kg slab'],
            ['Delhivery_500gm', 'Delhivery 500 g slab'],
            ['amazon_0_5kg', 'Amazon Shipping 500 g'],
            ['amazon_2kg', 'Amazon Shipping 2 kg'],
            ['Amazon_500gm', 'Amazon Shipping 500 g (alternate network)'],
            ['Amazon_1kg', 'Amazon Shipping 1 kg'],
            ['Amazon_2kg', 'Amazon Shipping 2 kg (alternate network)'],
            ['dtdc_surface_500gm', 'DTDC Surface 500 g'],
            ['dtdc_surface_1kg', 'DTDC Surface 1 kg'],
            ['dtdc_air', 'DTDC Air'],
            ['shadowfax', 'Shadowfax'],
            ['sell_Ekart_2KG', 'Ekart 2 kg'],
            ['xpressbee_250gms', 'Xpressbees 250 g'],
            ['bluedart_2kg_surface', 'Blue Dart Surface 2 kg'],
            ['delhivery_250gms', 'Delhivery 250 g'],
            ['bluedart_air_500gms', 'Blue Dart Air 500 g'],
            ['bluedart_surface_500gms', 'Blue Dart Surface 500 g'],
        ];
    }

    public function webhookExample(): array
    {
        return [
            'awb' => '14344940012345',
            'order_id' => 48213,
            'status' => 'Delivered',
            'scan_type' => 'DL',
            'remarks' => 'Delivered to customer',
            'courier_provider' => 'Shipxpeed',
            'original_webhook_data' => ['…raw update from the courier…'],
        ];
    }

    /** Postman v2.1 collection generated from endpoints(). */
    public function postman(string $baseUrl): array
    {
        $groups = [];
        foreach ($this->endpoints() as $e) {
            $headers = [
                ['key' => 'Content-Type', 'value' => 'application/json'],
                ['key' => 'Accept', 'value' => 'application/json'],
            ];
            if ($e['auth']) {
                $headers[] = ['key' => 'Authorization', 'value' => '{{token}}'];
            }
            $item = [
                'name' => $e['title'],
                'request' => [
                    'method' => $e['method'],
                    'header' => $headers,
                    'body' => ['mode' => 'raw', 'raw' => json_encode($e['request'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 'options' => ['raw' => ['language' => 'json']]],
                    'url' => ['raw' => '{{base_url}}' . $e['path'], 'host' => ['{{base_url}}'], 'path' => array_values(array_filter(explode('/', $e['path'])))],
                    'description' => $e['summary'],
                ],
                'response' => [],
            ];
            if ($e['id'] === 'auth') {
                $item['event'] = [[
                    'listen' => 'test',
                    'script' => ['type' => 'text/javascript', 'exec' => [
                        'const r = pm.response.json();',
                        'if (r.token) { pm.collectionVariables.set("token", r.token); }',
                    ]],
                ]];
            }
            $groups[$e['group']][] = $item;
        }

        $items = [];
        foreach ($groups as $name => $list) {
            $items[] = ['name' => $name, 'item' => $list];
        }

        return [
            'info' => [
                'name' => 'Shipxpeed Seller API',
                'description' => 'Run "Generate token" first; the token is saved to the collection automatically.',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'variable' => [
                ['key' => 'base_url', 'value' => rtrim($baseUrl, '/')],
                ['key' => 'token', 'value' => ''],
            ],
            'item' => $items,
        ];
    }
}
