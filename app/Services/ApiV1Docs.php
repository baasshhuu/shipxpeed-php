<?php

namespace App\Services;

use App\Support\ApiV1\Couriers;
use App\Support\ApiV1\Present;

/**
 * Single source for the API v1 documentation: the public page (/developers), its code samples,
 * the Postman collection and the Markdown export are all generated from this class.
 * The courier list is read live from the panel, so newly approved couriers appear automatically.
 */
class ApiV1Docs
{
    public const VERSION = 'v1';

    private function addr(): array
    {
        return ['name' => 'Priya Sharma', 'phone' => '9876543210', 'email' => 'priya@example.com', 'address' => '12 Marine Drive, Flat 4B',
            'address_2' => 'Near Churchgate', 'pincode' => '400001', 'city' => 'Mumbai', 'state' => 'Maharashtra'];
    }

    private function shipment(array $over = []): array
    {
        return array_replace([
            'shipment_id' => 48211, 'type' => 'b2c', 'order_number' => 'SPX#48211', 'client_order_id' => 'WEB-1001',
            'awb_number' => '14674857982103', 'courier_name' => 'Delhivery Surface', 'status' => 'booked',
            'status_text' => 'AWB generated, waiting for pickup', 'courier_status' => 'assigned', 'payment_type' => 'cod',
            'order_amount' => 1299, 'cod_amount' => 1299, 'shipping_charge' => 165.2, 'weight_g' => 900,
            'dimensions_cm' => ['length' => 20, 'breadth' => 15, 'height' => 8],
            'consignee' => ['name' => 'Priya Sharma', 'phone' => '9876543210', 'city' => 'Mumbai', 'state' => 'Maharashtra', 'pincode' => '400001'],
            'pickup' => ['warehouse_name' => 'Ghonda WH', 'city' => 'Delhi', 'state' => 'Delhi', 'pincode' => '110053'],
            'label_url' => null, 'tracking_url' => 'https://shipxpeed.tech/track?awb=14674857982103', 'booking_error' => null,
            'created_at' => '2026-11-03T10:21:40+05:30', 'shipped_at' => '2026-11-03T10:21:41+05:30',
        ], $over);
    }

    private function ok($data, string $msg = 'OK'): array
    {
        return ['success' => true, 'message' => $msg, 'data' => $data];
    }

    private function err(string $msg, string $code, array $extra = []): array
    {
        return ['success' => false, 'message' => $msg, 'error' => ['code' => $code] + $extra];
    }

    private function b2bShipment(array $over = []): array
    {
        return array_replace([
            'shipment_id' => 312, 'type' => 'b2b', 'order_ref' => 'B2B000312', 'awb_number' => '90412233451', 'lr_number' => 'LR77812',
            'courier_name' => 'Delhivery LTL', 'status' => 'booked', 'status_text' => 'AWB generated, waiting for pickup',
            'courier_status' => null, 'invoice_no' => 'INV-2026-0091', 'invoice_amount' => 42000, 'payment_type' => 'prepaid',
            'cod_amount' => 0, 'total_boxes' => 3, 'total_weight_kg' => 36, 'charged_weight_kg' => 36, 'shipping_charge' => 1416,
            'tat_days' => 4, 'consignee' => ['name' => 'Kiran Traders', 'company' => 'Kiran Traders Pvt Ltd', 'phone' => '9988776655',
                'city' => 'Mumbai', 'state' => 'Maharashtra', 'pincode' => '400069'],
            'pickup' => ['warehouse_id' => 7, 'city' => 'Delhi', 'pincode' => '110053'], 'error' => null,
            'created_at' => '2026-11-03T11:02:10+05:30', 'booked_at' => '2026-11-03T11:02:14+05:30',
        ], $over);
    }

    private function boxes(): array
    {
        return [['count' => 3, 'length_cm' => 40, 'breadth_cm' => 30, 'height_cm' => 30, 'weight_kg' => 12]];
    }

    private function boxFields(): array
    {
        return [
            ['boxes', 'array', true, 'One row per box size (1-50 rows).'],
            ['boxes[].count', 'integer', true, 'Number of boxes of this size.'],
            ['boxes[].length_cm / breadth_cm / height_cm', 'number', true, 'Box size in cm.'],
            ['boxes[].weight_kg', 'number', true, 'Dead weight of ONE box in kg.'],
        ];
    }

    public function endpoints(): array
    {
        $rate = fn ($id, $code, $name, $mode, $f, $c, $t, $rank, $edd = null) => ['courier_id' => $id, 'courier_code' => $code, 'courier_name' => $name,
            'brand' => Couriers::brand($name), 'mode' => $mode, 'logo' => null, 'zone' => 'D', 'freight_charge' => $f, 'cod_charge' => $c,
            'total_charge' => $t, 'gst_included' => true, 'chargeable_weight_g' => 1000, 'estimated_delivery_date' => $edd, 'rank' => $rank];
        $consigneeFields = [
            ['consignee.name', 'string', true, 'Buyer name.'],
            ['consignee.phone', 'string', true, '10-digit Indian mobile (+91 allowed).'],
            ['consignee.email', 'string', false, 'Buyer email.'],
            ['consignee.address', 'string', true, 'House / street (5-300 characters).'],
            ['consignee.address_2', 'string', false, 'Area / landmark.'],
            ['consignee.pincode', 'string', true, '6-digit delivery pincode.'],
            ['consignee.city', 'string', true, 'City.'],
            ['consignee.state', 'string', true, 'State, e.g. Maharashtra.'],
        ];

        return [
            /* ------------------------------ getting started ------------------------------ */
            [
                'id' => 'account', 'group' => 'Account', 'title' => 'Check your key / account', 'method' => 'GET', 'path' => '/v1/account',
                'summary' => 'Confirms the API key works and returns the account it belongs to, with the live wallet balance. Call this first when you set up an integration.',
                'fields' => [], 'request' => null,
                'response' => $this->ok(['client_id' => 501, 'name' => 'Acme Store', 'email' => 'ops@acme.in', 'phone' => '9810000001',
                    'kyc_status' => 'approved', 'wallet_balance' => 4834.8, 'negative_balance_allowed' => false, 'api_version' => 'v1']),
                'errors' => [[401, $this->err('Invalid API key.', 'unauthenticated')], [403, $this->err('This account is not active. Contact support@shipxpeed.com.', 'account_inactive')]],
            ],
            [
                'id' => 'wallet', 'group' => 'Account', 'title' => 'Wallet balance & transactions', 'method' => 'GET', 'path' => '/v1/wallet',
                'summary' => 'Current balance and wallet ledger, newest first. Every booking is a debit; cancellations and recharges are credits.',
                'fields' => [['page', 'integer', false, 'Page number (query string). Default 1.'], ['per_page', 'integer', false, '1-100. Default 25.']],
                'query' => ['page' => 1, 'per_page' => 25], 'request' => null,
                'response' => $this->ok(['balance' => 4834.8, 'currency' => 'INR', 'page' => 1, 'per_page' => 25, 'total' => 2, 'transactions' => [
                    ['id' => 9921, 'type' => 'debit', 'amount' => 165.2, 'description' => 'Order created', 'reference' => null, 'created_at' => '2026-11-03T10:21:41+05:30'],
                    ['id' => 9800, 'type' => 'credit', 'amount' => 5000, 'description' => 'Recharge', 'reference' => 'CF-77812', 'created_at' => '2026-11-01T09:00:00+05:30'],
                ]]),
                'errors' => [],
            ],
            [
                'id' => 'couriers', 'group' => 'Account', 'title' => 'Couriers live on your account', 'method' => 'GET', 'path' => '/v1/couriers',
                'summary' => 'Every courier you can ship with right now: B2C, reverse and B2B. The list follows the switches in the Shipxpeed panel, so a courier that is approved for you appears here (and in rates) automatically, and one that is switched off disappears.',
                'notes' => ['courier_code is stable: use it to pick a courier in Create shipment. courier_id in the rates response is what a specific quote books.'],
                'fields' => [['type', 'string', false, 'b2c, reverse or b2b (query string). Omit for all.']],
                'query' => ['type' => 'b2c'], 'request' => null,
                'response' => $this->ok(['count' => 2, 'couriers' => [
                    ['courier_code' => 'bluedart-surface-prime', 'name' => 'Bluedart Surface Prime', 'brand' => 'Blue Dart', 'type' => 'b2c', 'mode' => 'surface', 'logo' => 'https://shipxpeed.tech/brands/bluedart.png', 'live' => true, 'cod' => true, 'prepaid' => true],
                    ['courier_code' => 'delhivery-surface', 'name' => 'Delhivery Surface', 'brand' => 'Delhivery', 'type' => 'b2c', 'mode' => 'surface', 'logo' => 'https://shipxpeed.tech/brands/delhivery.png', 'live' => true, 'cod' => true, 'prepaid' => true],
                ]]),
                'errors' => [[422, $this->err('type must be b2c, reverse or b2b.', 'validation_failed')]],
            ],
            [
                'id' => 'rate-card', 'group' => 'Account', 'title' => 'Your rate card', 'method' => 'GET', 'path' => '/v1/rate-card',
                'summary' => 'Your zone-wise B2C rates as set in the panel (your own rates, or the default card where you have none). Base rates before GST.',
                'fields' => [], 'request' => null,
                'response' => $this->ok(['zones' => ['A', 'B', 'C', 'D', 'E'], 'slab_grams' => 500, 'gst_percent' => 18,
                    'formula' => 'first 500 g + each additional 500 g; COD = max(cod fixed, cod % of COD amount); 18% GST added on top',
                    'couriers' => [['courier_code' => 'delhivery-surface', 'courier_name' => 'Delhivery Surface',
                        'zones' => ['D' => ['first_500g' => 60, 'additional_500g' => 50, 'cod_fixed' => 30, 'cod_percent' => 2]]]]]),
                'errors' => [],
            ],
            [
                'id' => 'pincode', 'group' => 'Account', 'title' => 'Pincode lookup', 'method' => 'GET', 'path' => '/v1/pincodes/{pincode}',
                'summary' => 'State for a pincode (used for zones). Rates already do this for you; use it to validate addresses.',
                'fields' => [['pincode', 'string', true, '6 digits, in the path.']], 'example_path' => '/v1/pincodes/400001', 'request' => null,
                'response' => $this->ok(['pincode' => '400001', 'state' => 'Maharashtra']),
                'errors' => [[404, $this->err('Pincode not found.', 'not_found')]],
            ],
            [
                'id' => 'warehouses', 'group' => 'Account', 'title' => 'List pickup warehouses', 'method' => 'GET', 'path' => '/v1/warehouses',
                'summary' => 'Your saved pickup addresses. Pass warehouse_id as the pickup in Create shipment.',
                'fields' => [], 'request' => null,
                'response' => $this->ok(['count' => 1, 'warehouses' => [['warehouse_id' => 7, 'name' => 'Ghonda WH', 'contact_name' => 'Ravi', 'phone' => '9811122233',
                    'email' => 'wh@acme.in', 'address' => 'U-152/3, Gali No. 6, Arvind Nagar', 'address_2' => 'Ghonda', 'pincode' => '110053', 'city' => 'Delhi', 'state' => 'Delhi', 'is_default' => true]]]),
                'errors' => [],
            ],
            [
                'id' => 'warehouse-create', 'group' => 'Account', 'title' => 'Add a pickup warehouse', 'method' => 'POST', 'path' => '/v1/warehouses',
                'summary' => 'Saves a pickup address. The first one becomes the default.',
                'fields' => [
                    ['name', 'string', true, 'Warehouse name, unique on your account (shown to the courier).'],
                    ['contact_name', 'string', true, 'Person at the pickup point.'],
                    ['phone', 'string', true, '10-digit mobile.'], ['email', 'string', false, 'Defaults to your account email.'],
                    ['address', 'string', true, 'Street address.'], ['address_2', 'string', false, 'Area / landmark.'],
                    ['pincode', 'string', true, '6 digits.'], ['city', 'string', true, ''], ['state', 'string', true, ''],
                    ['is_default', 'boolean', false, 'Make this the default warehouse.'],
                ],
                'request' => ['name' => 'Bhiwandi WH', 'contact_name' => 'Amit', 'phone' => '9822012345', 'address' => 'Plot 9, MIDC Road', 'pincode' => '421302', 'city' => 'Bhiwandi', 'state' => 'Maharashtra'],
                'status' => 201,
                'response' => $this->ok(['warehouse_id' => 8, 'name' => 'Bhiwandi WH', 'contact_name' => 'Amit', 'phone' => '9822012345', 'email' => 'ops@acme.in',
                    'address' => 'Plot 9, MIDC Road', 'address_2' => '', 'pincode' => '421302', 'city' => 'Bhiwandi', 'state' => 'Maharashtra', 'is_default' => false], 'Warehouse created'),
                'errors' => [[409, $this->err('A warehouse with this name already exists.', 'duplicate_warehouse')]],
            ],

            /* ------------------------------------ B2C ------------------------------------ */
            [
                'id' => 'b2c-rates', 'group' => 'B2C (forward)', 'title' => 'Fetch couriers & rates', 'method' => 'POST', 'path' => '/v1/b2c/rates',
                'summary' => 'Every courier that serves the lane for your account, with your price, cheapest first. Prices come from your own rate card in the panel and are exactly what your wallet is debited on booking (GST included).',
                'notes' => [
                    'Price = first 500 g + each additional 500 g (chargeable weight), plus COD charge for COD, plus 18% GST.',
                    'States are looked up from the pincodes; send origin_state / destination_state to skip the lookup.',
                    'Couriers switched off for your account never appear.',
                ],
                'fields' => [
                    ['origin_pincode', 'string', true, 'Pickup pincode.'], ['destination_pincode', 'string', true, 'Delivery pincode.'],
                    ['payment_type', 'string', true, 'cod or prepaid.'], ['order_amount', 'number', true, 'Order value in ₹.'],
                    ['cod_amount', 'number', false, 'Amount to collect (COD). Defaults to order_amount.'],
                    ['weight_g', 'number', true, 'Dead weight in grams.'],
                    ['length_cm / breadth_cm / height_cm', 'number', false, 'Box size in cm (default 10 each).'],
                    ['origin_state / destination_state', 'string', false, 'Skip the pincode lookup.'],
                    ['mode', 'string', false, 'surface or air: only that mode.'],
                ],
                'request' => ['origin_pincode' => '110053', 'destination_pincode' => '400001', 'payment_type' => 'cod', 'order_amount' => 1299,
                    'weight_g' => 900, 'length_cm' => 20, 'breadth_cm' => 15, 'height_cm' => 8],
                'response' => $this->ok(['origin_pincode' => '110053', 'destination_pincode' => '400001', 'payment_type' => 'cod', 'weight_g' => 900, 'count' => 2, 'couriers' => [
                    $rate('sxc_14', 'bluedart-surface-prime', 'Bluedart Surface Prime', 'surface', 100, 25, 147.5, 1, '2026-11-08'),
                    $rate('delhivery', 'delhivery-surface', 'Delhivery Surface', 'surface', 110, 30, 165.2, 2),
                ]], 'Rates fetched'),
                'response_fields' => [
                    ['courier_id', 'string', 'Pass to Create shipment to book exactly this quote.'],
                    ['courier_code', 'string', 'Stable courier code (same as GET /v1/couriers).'],
                    ['total_charge', 'number', 'What your wallet is debited, ₹, GST included.'],
                    ['freight_charge / cod_charge', 'number', 'Break-up before GST.'],
                    ['zone', 'string', 'Zone A-E for this lane.'], ['rank', 'integer', '1 = cheapest.'],
                ],
                'errors' => [[422, $this->err('The origin pincode field must be 6 digits.', 'validation_failed', ['fields' => ['origin_pincode' => ['The origin pincode field must be 6 digits.']]])]],
            ],
            [
                'id' => 'b2c-create', 'group' => 'B2C (forward)', 'title' => 'Create & book shipment', 'method' => 'POST', 'path' => '/v1/b2c/shipments',
                'summary' => 'Creates the shipment and books it with a courier in one call. Returns the AWB. Your wallet is debited the rate shown by Fetch rates.',
                'notes' => [
                    'Courier choice: courier_id (from rates) books that exact quote; courier_code books that courier at its current price; send neither to book the cheapest.',
                    'client_order_id is your own order number. Sending the same one again returns 409 with the existing shipment, so retries never ship twice.',
                    'If the courier refuses (for example low wallet balance), the shipment is saved unbooked and you get 422 booking_failed with its shipment_id. Fix the cause and call Book shipment.',
                    'Need at least ₹150 in the wallet, as in the panel (unless negative balance is enabled on your account).',
                ],
                'fields' => array_merge([
                    ['client_order_id', 'string', false, 'Your order number (recommended).'],
                    ['payment_type', 'string', true, 'cod or prepaid.'], ['cod_amount', 'number', 'COD', 'Amount to collect. Required for COD.'],
                    ['order_amount', 'number', false, 'Order value. Defaults to the items total.'],
                    ['weight_g', 'number', true, 'Dead weight in grams.'], ['length_cm / breadth_cm / height_cm', 'number', true, 'Box size in cm.'],
                    ['courier_id | courier_code', 'string', false, 'Which courier (see notes).'],
                    ['pickup.warehouse_id', 'integer', true, 'Saved warehouse, or send pickup.name, phone, address, pincode, city, state instead.'],
                    ['rto', 'object', false, 'Return address, same shape as pickup. Defaults to pickup.'],
                ], $consigneeFields, [
                    ['items', 'array', true, '1-50 items.'], ['items[].name', 'string', true, ''], ['items[].sku', 'string', false, ''],
                    ['items[].qty', 'integer', true, ''], ['items[].price', 'number', true, 'Unit price ₹.'],
                ]),
                'request' => ['client_order_id' => 'WEB-1001', 'payment_type' => 'cod', 'cod_amount' => 1299, 'weight_g' => 900,
                    'length_cm' => 20, 'breadth_cm' => 15, 'height_cm' => 8, 'courier_code' => 'delhivery-surface',
                    'pickup' => ['warehouse_id' => 7], 'consignee' => $this->addr(),
                    'items' => [['name' => 'Cotton Kurta', 'sku' => 'KRT-01', 'qty' => 1, 'price' => 1299]]],
                'status' => 201,
                'response' => $this->ok($this->shipment() + ['charged' => 165.2], 'Shipment booked'),
                'errors' => [
                    [409, $this->err('A shipment with this client_order_id already exists.', 'duplicate_order', ['shipment' => ['shipment_id' => 48211, 'awb_number' => '14674857982103', '...' => '...']])],
                    [422, $this->err('Shipment created but not booked: Insufficient wallet balance. Please recharge your wallet.', 'booking_failed', ['shipment' => ['shipment_id' => 48212, 'status' => 'new', '...' => '...'], 'retry' => 'POST /api/v1/b2c/shipments/48212/book'])],
                    [422, $this->err('That courier is not available on this lane for your account. Pick one from POST /api/v1/b2c/rates.', 'courier_unavailable')],
                ],
            ],
            [
                'id' => 'b2c-book', 'group' => 'B2C (forward)', 'title' => 'Book an unbooked shipment', 'method' => 'POST', 'path' => '/v1/b2c/shipments/{shipment_id}/book',
                'summary' => 'Books a shipment that was created but not booked (booking_failed). Send courier_id or courier_code, or nothing for the cheapest.',
                'fields' => [['courier_id | courier_code', 'string', false, 'Which courier.']],
                'example_path' => '/v1/b2c/shipments/48212/book', 'request' => ['courier_code' => 'delhivery-surface'],
                'response' => $this->ok($this->shipment(['shipment_id' => 48212]), 'Shipment booked'),
                'errors' => [[409, $this->err('This shipment is already booked.', 'already_booked')]],
            ],

            /* ---------------------------------- reverse ---------------------------------- */
            [
                'id' => 'reverse-rates', 'group' => 'Reverse (returns)', 'title' => 'Reverse pickup rate', 'method' => 'POST', 'path' => '/v1/reverse/rates',
                'summary' => 'Price to pick a parcel up from the buyer and bring it back to your warehouse (prepaid, from your rate card).',
                'fields' => [['pickup_pincode', 'string', true, 'Buyer pincode (where it is picked up).'], ['delivery_pincode', 'string', true, 'Your warehouse pincode.'],
                    ['weight_g', 'number', true, 'Weight in grams.']],
                'request' => ['pickup_pincode' => '400001', 'delivery_pincode' => '110053', 'weight_g' => 600],
                'response' => $this->ok(['pickup_pincode' => '400001', 'delivery_pincode' => '110053', 'weight_g' => 600, 'count' => 1, 'couriers' => [[
                    'courier_id' => 'delhivery_reverse', 'courier_code' => 'delhivery-reverse', 'courier_name' => 'Delhivery Reverse', 'brand' => 'Delhivery',
                    'mode' => 'surface', 'zone' => 'D', 'freight_charge' => 110, 'cod_charge' => 0, 'total_charge' => 129.8, 'gst_included' => true, 'chargeable_weight_g' => 1000]]], 'Rates fetched'),
                'errors' => [[422, $this->err('Reverse pickup is not available from pincode 999999.', 'not_serviceable')]],
            ],
            [
                'id' => 'reverse-create', 'group' => 'Reverse (returns)', 'title' => 'Create & book reverse pickup', 'method' => 'POST', 'path' => '/v1/reverse/shipments',
                'summary' => 'Books a reverse pickup. Two ways: (a) send only awb_number of a DELIVERED forward shipment to create its return, or (b) send full details for a standalone pickup.',
                'notes' => ['Option (a) copies the buyer and items from the original order; one return per AWB.', 'Reverse pickups are prepaid; the wallet is debited the reverse rate.'],
                'fields' => [
                    ['awb_number', 'string', '(a)', 'Delivered forward AWB.'], ['reason', 'string', false, 'Customer return, Damaged product, Wrong item delivered, Size / fit issue, Quality issue, Exchange, Other.'],
                    ['warehouse_id', 'integer', '(b)', 'Where the parcel comes back to.'], ['pickup', 'object', '(b)', 'Buyer address: name, phone, address, pincode, city, state.'],
                    ['items', 'array', '(b)', 'name, qty, price.'], ['weight_g, length_cm, breadth_cm, height_cm', 'number', '(b)', 'Parcel size.'],
                    ['client_order_id', 'string', false, 'Your return reference (b).'],
                ],
                'request' => ['client_order_id' => 'RET-77', 'warehouse_id' => 7, 'weight_g' => 600, 'length_cm' => 20, 'breadth_cm' => 15, 'height_cm' => 8,
                    'pickup' => ['name' => 'Priya Sharma', 'phone' => '9876543210', 'address' => '12 Marine Drive, Flat 4B', 'pincode' => '400001', 'city' => 'Mumbai', 'state' => 'Maharashtra'],
                    'items' => [['name' => 'Cotton Kurta', 'qty' => 1, 'price' => 1299]]],
                'status' => 201,
                'response' => $this->ok($this->shipment(['shipment_id' => 48230, 'type' => 'reverse', 'client_order_id' => 'RET-77', 'awb_number' => '91558210092231',
                    'courier_name' => 'Delhivery Reverse', 'payment_type' => 'prepaid', 'cod_amount' => 0, 'shipping_charge' => 129.8]), 'Reverse pickup booked'),
                'errors' => [[422, $this->err('A return can only be created for a delivered shipment (current status: in_transit).', 'not_delivered')],
                    [409, $this->err('A return already exists for this AWB.', 'duplicate_return')]],
            ],

            /* ------------------------------------ B2B ------------------------------------ */
            [
                'id' => 'b2b-rates', 'group' => 'B2B (cargo)', 'title' => 'B2B rates', 'method' => 'POST', 'path' => '/v1/b2b/rates',
                'summary' => 'Multi-box / LTL rates from the B2B couriers approved for you, cheapest first, GST included.',
                'fields' => array_merge([
                    ['pickup_warehouse_id | origin_pincode', 'integer|string', true, 'Pickup.'], ['destination_pincode', 'string', true, ''],
                    ['invoice_amount', 'number', true, 'Invoice value ₹.'], ['payment_type', 'string', true, 'prepaid or cod.'],
                    ['cod_amount', 'number', 'COD', 'Less than invoice_amount.'], ['risk', 'string', false, "owner (default, cheaper) or carrier."],
                ], $this->boxFields()),
                'request' => ['pickup_warehouse_id' => 7, 'destination_pincode' => '400069', 'invoice_amount' => 42000, 'payment_type' => 'prepaid', 'boxes' => $this->boxes()],
                'response' => $this->ok(['origin_pincode' => '110053', 'destination_pincode' => '400069', 'count' => 1, 'couriers' => [[
                    'courier_id' => '11', 'courier_code' => 'delhivery-ltl', 'courier_name' => 'Delhivery LTL', 'brand' => 'Delhivery', 'logo' => null, 'zone' => 'D',
                    'tat_days' => 4, 'charged_weight_kg' => 36, 'total_charge' => 1416, 'gst_included' => true, 'note' => null]]], 'Rates fetched'),
                'errors' => [[403, $this->err('B2B shipping is not enabled for your account yet. Contact support@shipxpeed.com.', 'b2b_disabled')]],
            ],
            [
                'id' => 'b2b-create', 'group' => 'B2B (cargo)', 'title' => 'Create & book B2B shipment', 'method' => 'POST', 'path' => '/v1/b2b/shipments',
                'summary' => 'Creates and books a B2B shipment. Upload the invoice as a file (multipart/form-data) or base64 in JSON. Invoices of ₹50,000 or more need the e-way bill number and file.',
                'notes' => ['One live shipment per invoice_no (a repeat returns 409).', 'Send max_price to refuse booking if the final price is higher.', 'Documents (label, manifest) are available after booking.'],
                'fields' => array_merge([
                    ['pickup_warehouse_id', 'integer', true, ''], ['return_warehouse_id', 'integer', false, 'Defaults to pickup.'],
                    ['consignee.name / phone / address / pincode / city / state', 'string', true, 'Receiver.'],
                    ['consignee.company / gstin / email / address_2 / landmark', 'string', false, ''],
                    ['invoice_no', 'string', true, ''], ['invoice_date', 'date', false, 'YYYY-MM-DD, default today.'], ['invoice_amount', 'number', true, ''],
                    ['payment_type', 'string', true, 'prepaid or cod.'], ['cod_amount', 'number', 'COD', ''], ['product_name', 'string', true, ''],
                    ['risk', 'string', false, 'owner or carrier.'],
                    ['invoice_file | invoice_file_base64', 'file | string', true, 'PDF/JPG/PNG up to 5 MB.'],
                    ['ewaybill_no', 'string', '≥ ₹50k', '12 digits.'], ['ewaybill_file | ewaybill_file_base64', 'file | string', '≥ ₹50k', ''],
                    ['courier_id | courier_code', 'string', false, 'From B2B rates; default cheapest.'], ['max_price', 'number', false, ''],
                ], $this->boxFields()),
                'request' => ['pickup_warehouse_id' => 7, 'consignee' => ['name' => 'Kiran Traders', 'company' => 'Kiran Traders Pvt Ltd', 'phone' => '9988776655',
                    'gstin' => '27ABCDE1234F1Z5', 'address' => 'Gala 12, Andheri East', 'pincode' => '400069', 'city' => 'Mumbai', 'state' => 'Maharashtra'],
                    'invoice_no' => 'INV-2026-0091', 'invoice_amount' => 42000, 'payment_type' => 'prepaid', 'product_name' => 'Garments',
                    'invoice_file_base64' => 'JVBERi0xLjQK...', 'boxes' => $this->boxes()],
                'status' => 201,
                'response' => $this->ok($this->b2bShipment(), 'Booked with Delhivery LTL - AWB 90412233451. ₹1,416.00 debited from wallet.'),
                'errors' => [[422, $this->err('ewaybill_no is required for invoices of ₹50,000 or more.', 'validation_failed')],
                    [422, $this->err('B2B shipment saved but not booked: Insufficient wallet balance.', 'booking_failed', ['retry' => 'POST /api/v1/b2b/shipments/B2B000313/book'])]],
            ],
            [
                'id' => 'b2b-book', 'group' => 'B2B (cargo)', 'title' => 'Book a saved B2B shipment', 'method' => 'POST', 'path' => '/v1/b2b/shipments/{order_ref}/book',
                'summary' => 'Books a B2B shipment that was saved but not booked. Send max_price to accept a changed final price.',
                'fields' => [['courier_id | courier_code', 'string', false, ''], ['max_price', 'number', false, '']],
                'example_path' => '/v1/b2b/shipments/B2B000313/book', 'request' => ['courier_code' => 'delhivery-ltl'],
                'response' => $this->ok($this->b2bShipment(['order_ref' => 'B2B000313']), 'Booked'), 'errors' => [],
            ],
            [
                'id' => 'b2b-show', 'group' => 'B2B (cargo)', 'title' => 'B2B shipment details / list', 'method' => 'GET', 'path' => '/v1/b2b/shipments/{order_ref}',
                'summary' => 'One B2B shipment by order_ref or AWB. GET /v1/b2b/shipments (with ?status=) lists them.',
                'fields' => [], 'example_path' => '/v1/b2b/shipments/B2B000312', 'request' => null,
                'response' => $this->ok($this->b2bShipment()), 'errors' => [[404, $this->err('B2B shipment not found.', 'not_found')]],
            ],
            [
                'id' => 'b2b-docs', 'group' => 'B2B (cargo)', 'title' => 'B2B label / manifest', 'method' => 'GET', 'path' => '/v1/b2b/shipments/{order_ref}/documents/{type}',
                'summary' => 'Download link for the courier label or manifest. type = label or manifest.',
                'fields' => [], 'example_path' => '/v1/b2b/shipments/B2B000312/documents/label', 'request' => null,
                'response' => $this->ok(['order_ref' => 'B2B000312', 'type' => 'label', 'url' => 'https://…/label.pdf', 'mime' => 'application/pdf']), 'errors' => [],
            ],
            [
                'id' => 'b2b-cancel', 'group' => 'B2B (cargo)', 'title' => 'Cancel B2B shipment', 'method' => 'POST', 'path' => '/v1/b2b/shipments/{order_ref}/cancel',
                'summary' => 'Cancels a B2B shipment (also works through Cancel shipments with its AWB).',
                'fields' => [], 'example_path' => '/v1/b2b/shipments/B2B000312/cancel', 'request' => [],
                'response' => $this->ok($this->b2bShipment(['status' => 'cancelled', 'status_text' => 'Cancelled']), 'Cancelled'), 'errors' => [],
            ],

            /* ------------------------------- after booking ------------------------------- */
            [
                'id' => 'shipments', 'group' => 'After booking', 'title' => 'List shipments', 'method' => 'GET', 'path' => '/v1/shipments',
                'summary' => 'Your B2C and reverse shipments, newest first, with normalised status.',
                'fields' => [['type', 'string', false, 'b2c or reverse.'], ['status', 'string', false, 'One of the status codes below.'],
                    ['from / to', 'date', false, 'Created between (YYYY-MM-DD).'], ['page / per_page', 'integer', false, 'per_page up to 100.']],
                'query' => ['type' => 'b2c', 'status' => 'in_transit', 'per_page' => 50], 'request' => null,
                'response' => $this->ok(['page' => 1, 'per_page' => 50, 'total' => 1, 'shipments' => [$this->shipment(['status' => 'in_transit', 'status_text' => 'Picked up / in transit'])]]),
                'errors' => [],
            ],
            [
                'id' => 'shipment', 'group' => 'After booking', 'title' => 'Shipment details', 'method' => 'GET', 'path' => '/v1/shipments/{ref}',
                'summary' => 'One shipment by AWB, shipment_id, order_number or your client_order_id. Finds B2B shipments too.',
                'fields' => [], 'example_path' => '/v1/shipments/14674857982103', 'request' => null,
                'response' => $this->ok($this->shipment()), 'errors' => [[404, $this->err('Shipment not found.', 'not_found')]],
            ],
            [
                'id' => 'track', 'group' => 'After booking', 'title' => 'Track shipment', 'method' => 'GET', 'path' => '/v1/track/{awb}',
                'summary' => 'Current status and scan history (newest first) for B2C, reverse or B2B.',
                'fields' => [], 'example_path' => '/v1/track/14674857982103', 'request' => null,
                'response' => $this->ok($this->shipment(['status' => 'in_transit', 'status_text' => 'Picked up / in transit', 'courier_status' => 'In Transit']) + [
                    'expected_delivery_date' => null, 'events' => [
                        ['status' => 'In Transit', 'location' => 'Delhi Hub', 'time' => '2026-11-03 18:40:12', 'remarks' => 'Bagged'],
                        ['status' => 'Manifested', 'location' => 'Delhi', 'time' => '2026-11-03 12:02:51', 'remarks' => null]]], 'Tracking fetched'),
                'errors' => [[404, $this->err('AWB not found in your account.', 'not_found')]],
            ],
            [
                'id' => 'label', 'group' => 'After booking', 'title' => 'Shipping label', 'method' => 'GET', 'path' => '/v1/shipments/{awb}/label',
                'summary' => 'PDF label in your label settings (Seller panel → Settings → Label: 4x6 thermal or A4, logo, fields). Returns a link valid for 7 days. Add ?format=pdf to get the PDF itself, or ?base64=1 to also get it inline.',
                'fields' => [['format', 'string', false, 'pdf: respond with the PDF file.'], ['base64', 'boolean', false, '1: include label_base64.']],
                'example_path' => '/v1/shipments/14674857982103/label', 'request' => null,
                'response' => $this->ok(['awb_numbers' => ['14674857982103'], 'count' => 1, 'label_url' => 'https://shipxpeed.tech/labels/api/Xk2…-14674857982103.pdf',
                    'format' => 'pdf', 'size' => '4x6', 'expires_at' => '2026-11-10T10:30:00+05:30'], 'Label ready'),
                'errors' => [[404, $this->err('AWB not found in your account.', 'not_found')]],
            ],
            [
                'id' => 'labels', 'group' => 'After booking', 'title' => 'Bulk labels (one PDF)', 'method' => 'POST', 'path' => '/v1/labels',
                'summary' => 'Up to 100 labels in one PDF.',
                'fields' => [['awb_numbers', 'array', true, 'AWBs.']], 'request' => ['awb_numbers' => ['14674857982103', '14674857982110']],
                'response' => $this->ok(['awb_numbers' => ['14674857982103', '14674857982110'], 'count' => 2, 'label_url' => 'https://shipxpeed.tech/labels/api/…pdf',
                    'format' => 'pdf', 'size' => '4x6', 'expires_at' => '2026-11-10T10:30:00+05:30'], 'Label ready'), 'errors' => [],
            ],
            [
                'id' => 'cancel', 'group' => 'After booking', 'title' => 'Cancel shipments', 'method' => 'POST', 'path' => '/v1/shipments/cancel',
                'summary' => 'Cancels up to 50 shipments (B2C, reverse or B2B AWBs). The shipping charge is refunded to your wallet when the courier confirms the cancellation.',
                'fields' => [['awb_numbers', 'array', true, 'AWBs (or a single awb_number).']], 'request' => ['awb_numbers' => ['14674857982103']],
                'response' => $this->ok(['cancelled' => 1, 'failed' => 0, 'results' => [['awb_number' => '14674857982103', 'success' => true, 'message' => 'Shipment cancelled successfully']]], '1 of 1 cancelled'),
                'errors' => [[422, $this->err('Order is already cancelled', 'cancel_failed')]],
            ],
            [
                'id' => 'ndr', 'group' => 'After booking', 'title' => 'NDR list', 'method' => 'GET', 'path' => '/v1/ndr',
                'summary' => 'Shipments with a failed delivery attempt waiting for your decision.',
                'fields' => [], 'request' => null,
                'response' => $this->ok(['total' => 1, 'shipments' => [$this->shipment(['status' => 'ndr', 'status_text' => 'Delivery attempt failed (NDR)', 'courier_status' => 'NDR'])
                    + ['ndr_reason' => 'Customer not available', 'allowed_actions' => ['re-attempt', 'change-address', 'change-phone', 'rto']]]]),
                'errors' => [],
            ],
            [
                'id' => 'ndr-action', 'group' => 'After booking', 'title' => 'NDR action', 'method' => 'POST', 'path' => '/v1/ndr/action',
                'summary' => 'Re-attempt delivery (optionally with a new address or phone) or return to origin. Sent to the courier through the same route as the panel and recorded in your NDR history.',
                'notes' => ['result sent_to_courier: the courier accepted it. needs_manual: recorded, and the Shipxpeed ops team raises it with the courier the same day.'],
                'fields' => [['awb_number', 'string', true, ''], ['action', 'string', true, 're-attempt, change-address, change-phone or rto.'],
                    ['reattempt_date', 'date', false, 'YYYY-MM-DD; default tomorrow.'], ['address / landmark', 'string', 'change-address', 'New address.'],
                    ['phone', 'string', 'change-phone', 'New 10-digit mobile.'], ['remarks', 'string', false, '']],
                'request' => ['awb_number' => '14674857982103', 'action' => 're-attempt', 'reattempt_date' => '2026-11-05', 'remarks' => 'Call before delivery'],
                'response' => $this->ok(['awb_number' => '14674857982103', 'action' => 're-attempt', 'result' => 'sent_to_courier'], 'Request sent to the courier'),
                'errors' => [[422, $this->err('Courier rejected the request: Delhivery rejected the re-attempt', 'ndr_failed')]],
            ],

            /* ---------------------------------- webhooks ---------------------------------- */
            [
                'id' => 'webhook-save', 'group' => 'Webhooks', 'title' => 'Set webhook URL', 'method' => 'POST', 'path' => '/v1/webhooks',
                'summary' => 'Your HTTPS URL for status updates (one per account; a new one replaces the old). GET /v1/webhooks shows it.',
                'fields' => [['url', 'string', true, 'https URL.']], 'request' => ['url' => 'https://acme.in/hooks/shipxpeed'],
                'response' => $this->ok(['webhook_id' => 3, 'url' => 'https://acme.in/hooks/shipxpeed', 'active' => true, 'events' => ['shipment.status_updated']], 'Webhook saved'),
                'errors' => [[422, $this->err('The webhook URL must use https.', 'validation_failed')]],
            ],
            [
                'id' => 'webhook-test', 'group' => 'Webhooks', 'title' => 'Send a test event', 'method' => 'POST', 'path' => '/v1/webhooks/test',
                'summary' => 'Sends a sample shipment.status_updated event to your URL and reports what your server answered.',
                'fields' => [], 'request' => [],
                'response' => $this->ok(['url' => 'https://acme.in/hooks/shipxpeed', 'http_status' => 200, 'delivered' => true, 'payload' => $this->webhookExample(true)], 'Test event delivered'),
                'errors' => [[502, $this->err('Could not reach your webhook URL: Connection timed out', 'webhook_unreachable')]],
            ],
        ];
    }

    public function webhookExample(bool $test = false): array
    {
        $d = ['awb_number' => '14674857982103', 'shipment_id' => 48211, 'type' => 'b2c', 'client_order_id' => 'WEB-1001', 'order_number' => 'SPX#48211',
            'courier_name' => 'Delhivery Surface', 'status' => 'out_for_delivery', 'previous_status' => 'in_transit', 'courier_status' => 'Out for delivery',
            'time' => '2026-11-05T09:12:44+05:30'];
        return $test ? ['event' => 'shipment.status_updated', 'test' => true, 'data' => $d] : ['event' => 'shipment.status_updated', 'data' => $d];
    }

    public function errorCodes(): array
    {
        return [
            ['unauthenticated', 401, 'API key missing or wrong.'],
            ['account_inactive', 403, 'Account suspended or not approved.'],
            ['rate_limited', 429, 'More than 120 calls a minute. Wait for Retry-After seconds.'],
            ['validation_failed', 422, 'A field is missing or invalid; see error.fields.'],
            ['not_found', 404, 'No such shipment / AWB / endpoint on your account.'],
            ['duplicate_order', 409, 'Same client_order_id / invoice_no already shipped; error.shipment has it.'],
            ['courier_unavailable', 422, 'Chosen courier does not serve this lane for you; error.available lists those that do.'],
            ['not_serviceable', 422, 'No courier serves this lane.'],
            ['booking_failed', 422, 'Saved but the courier did not book (often wallet balance). Retry with the book endpoint.'],
            ['b2b_disabled', 403, 'B2B is not enabled on your account.'],
            ['cancel_failed / ndr_failed / label_failed', '422 / 500', 'The courier refused or is unavailable; message says why.'],
        ];
    }

    /** Live couriers for the docs page (couriers on by default in the panel). */
    public function liveCouriers(): array
    {
        try {
            return Couriers::present(Couriers::forSeller(0));
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function statuses(): array
    {
        return Present::STATUSES;
    }

    /* ------------------------------------------------------------------------- */

    public function url(array $e, string $base): string
    {
        $path = $e['example_path'] ?? $e['path'];
        $q = !empty($e['query']) ? '?' . http_build_query($e['query']) : '';
        return rtrim($base, '/') . $path . $q;
    }

    /** cURL, PHP, Node and Python samples for one endpoint. */
    public function samples(array $e, string $base, string $key = 'YOUR_API_KEY'): array
    {
        $url = $this->url($e, $base);
        $m = $e['method'];
        $body = $e['request'];
        $json = $body !== null ? json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;
        $post = $m !== 'GET';

        $curl = "curl -X $m '$url' \\\n  -H 'X-API-Key: $key' \\\n  -H 'Accept: application/json'"
            . ($post ? " \\\n  -H 'Content-Type: application/json' \\\n  -d '" . str_replace("'", "'\\''", $json ?? '{}') . "'" : '');

        $php = "\$ch = curl_init('$url');\ncurl_setopt_array(\$ch, [\n    CURLOPT_RETURNTRANSFER => true,\n    CURLOPT_CUSTOMREQUEST  => '$m',\n"
            . "    CURLOPT_HTTPHEADER     => ['X-API-Key: $key', 'Accept: application/json'" . ($post ? ", 'Content-Type: application/json'" : '') . "],\n"
            . ($post ? "    CURLOPT_POSTFIELDS     => json_encode(" . $this->phpLit($body ?? [], 1) . "),\n" : '')
            . "]);\n\$res = json_decode(curl_exec(\$ch), true);";

        $node = "const res = await fetch('$url', {\n  method: '$m',\n  headers: { 'X-API-Key': '$key', 'Accept': 'application/json'"
            . ($post ? ", 'Content-Type': 'application/json'" : '') . " },\n" . ($post ? "  body: JSON.stringify(" . $this->indent($json ?? '{}', 2) . "),\n" : '')
            . "});\nconst data = await res.json();";

        $py = "import requests\n\nres = requests." . strtolower($m) . "(\n    \"$url\",\n    headers={\"X-API-Key\": \"$key\"},\n"
            . ($post ? "    json=" . $this->indent($this->pyLit($json ?? '{}'), 4) . ",\n" : '') . ")\nprint(res.json())";

        return ['cURL' => $curl, 'PHP' => $php, 'Node.js' => $node, 'Python' => $py];
    }

    private function indent(string $s, int $n): string
    {
        return str_replace("\n", "\n" . str_repeat(' ', $n), $s);
    }

    private function pyLit(string $json): string
    {
        return str_replace([': true', ': false', ': null'], [': True', ': False', ': None'], $json);
    }

    private function phpLit($v, int $lvl): string
    {
        if (!is_array($v)) {
            return var_export($v, true);
        }
        if (!$v) {
            return '[]';
        }
        $pad = str_repeat('    ', $lvl + 1);
        $list = array_is_list($v);
        $out = [];
        foreach ($v as $k => $x) {
            $out[] = $pad . ($list ? '' : var_export($k, true) . ' => ') . $this->phpLit($x, $lvl + 1);
        }
        return "[\n" . implode(",\n", $out) . ",\n" . str_repeat('    ', $lvl) . ']';
    }

    /** Postman v2.1 collection. */
    public function postman(string $base): array
    {
        $groups = [];
        foreach ($this->endpoints() as $e) {
            $path = ltrim($e['example_path'] ?? $e['path'], '/');
            $req = [
                'method' => $e['method'],
                'header' => array_values(array_filter([
                    ['key' => 'X-API-Key', 'value' => '{{api_key}}'], ['key' => 'Accept', 'value' => 'application/json'],
                    $e['method'] !== 'GET' ? ['key' => 'Content-Type', 'value' => 'application/json'] : null,
                ])),
                'url' => ['raw' => '{{base_url}}/' . $path . (!empty($e['query']) ? '?' . http_build_query($e['query']) : ''), 'host' => ['{{base_url}}'],
                    'path' => explode('/', $path), 'query' => array_map(fn ($k, $v) => ['key' => $k, 'value' => (string) $v], array_keys($e['query'] ?? []), $e['query'] ?? [])],
                'description' => $e['summary'],
            ];
            if ($e['method'] !== 'GET') {
                $req['body'] = ['mode' => 'raw', 'raw' => json_encode($e['request'] ?? new \stdClass(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'options' => ['raw' => ['language' => 'json']]];
            }
            $groups[$e['group']][] = ['name' => $e['title'], 'request' => $req];
        }
        return [
            'info' => ['name' => 'Shipxpeed API v1', 'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
                'description' => 'Set the api_key variable to your key from Seller panel → API.'],
            'variable' => [['key' => 'base_url', 'value' => rtrim($base, '/')], ['key' => 'api_key', 'value' => 'YOUR_API_KEY']],
            'item' => array_map(fn ($g, $items) => ['name' => $g, 'item' => $items], array_keys($groups), $groups),
        ];
    }
}
