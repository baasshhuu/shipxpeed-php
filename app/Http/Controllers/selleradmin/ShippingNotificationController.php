<?php

namespace App\Http\Controllers\selleradmin;

use App\Models\Recharge;
use App\Models\ShippingNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Redirect;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Helper\Helper;
use App\Models\Order;
use App\Models\LogisticProvider;
use App\Services\XpressBeesService;
use App\Models\SellerList;


use Illuminate\Support\Facades\Session;
use Barryvdh\DomPDF\Facade\Pdf;

use App\Models\AppSetting;
use App\Models\RateCard;
use App\Models\SellerAgreement;
use App\Models\SellerBankDetail;
use App\Models\{Buyer, OrderItem, Warehouse, OrderPackageDetail};
use App\Models\SellerAddress;
use App\Models\State;
use App\Models\City;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Excel as ExcelFormat;
use App\Imports\OrdersImport;

class ShippingNotificationController extends Controller
{
	// List notifications
	public function index()
	{

        
      $seller = Auth::guard('seller')->user();
        $sellerId = Auth::guard('seller')->id();
        

        $totalAmount = 0;
        if ($seller && $seller->status == 1) {
            // $totalAmount = Recharge::where(['seller_id' => $seller->id,'status' => '1'])->sum('amount'); 
            $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
                ->where('status', 1)
                ->where('type', 'Credit')
                ->sum('amount');

            $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
                ->where('type', 'Debit')
                ->sum('amount');

            $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;

        }

		// Get notifications - show global settings with seller-specific overrides
		$globalNotifications = ShippingNotification::whereNull('seller_id')->get();
		$sellerNotifications = ShippingNotification::where('seller_id', $sellerId)->get();
		
		// Merge global and seller-specific notifications
		$notifications = collect();
		foreach ($globalNotifications as $global) {
			// Check if seller has a specific setting for this notification
			$sellerSpecific = $sellerNotifications->where('notification_type', $global->notification_type)
				->where('order_status', $global->order_status)
				->first();
			
			if ($sellerSpecific) {
				// Use seller-specific setting
				$notifications->push($sellerSpecific);
			} else {
				// Use global setting but mark it as global for the view
				$global->is_global = true;
				$notifications->push($global);
			}
		}
		
		return \view('sellerdashboard.shipping-notification.index', compact('notifications', 'totalAmount','seller'));
	}

	// Enable/disable notification
	public function toggle($id)
	{
		$sellerId = Auth::guard('seller')->id();
		
		// First, get the global notification template
		$globalNotification = ShippingNotification::findOrFail($id);
		
		// Check if seller already has a specific entry for this notification
		$sellerNotification = ShippingNotification::where('seller_id', $sellerId)
			->where('notification_type', $globalNotification->notification_type)
			->where('order_status', $globalNotification->order_status)
			->first();
		
		if ($sellerNotification) {
			// Update existing seller-specific entry
			$sellerNotification->enabled = !$sellerNotification->enabled;
			$sellerNotification->save();
		} else {
			// Create new seller-specific entry
			ShippingNotification::create([
				'seller_id' => $sellerId,
				'notification_type' => $globalNotification->notification_type,
				'order_status' => $globalNotification->order_status,
				'enabled' => !$globalNotification->enabled, // Toggle from global setting
				'template' => $globalNotification->template ?? $this->getDefaultTemplate($globalNotification->notification_type, $globalNotification->order_status),
			]);
		}
		
		return \redirect()->back()->with('success', 'Notification setting updated successfully!');
	}

	// Update template
	public function updateTemplate(Request $request, $id)
	{
		$sellerId = Auth::guard('seller')->id();
		$notification = ShippingNotification::where('id', $id)
			->where('seller_id', $sellerId)
			->firstOrFail();
		$notification->template = $request->input('template');
		$notification->save();
		return \redirect()->back();
	}

	// Send notification (stub)
	public function send($id, $data)
	{
		$sellerId = Auth::guard('seller')->id();
		$notification = ShippingNotification::where('id', $id)
			->where('seller_id', $sellerId)
			->firstOrFail();
		if ($notification->enabled) {
			if ($notification->notification_type === 'email') {
				// Send email logic here
			} elseif ($notification->notification_type === 'whatsapp') {
				// Send WhatsApp logic here
			}
		}
	}

	// Create default notification settings for a seller
	public function createDefaultNotifications($sellerId)
	{
		$statuses = ['transit', 'out for delivery', 'delivered', 'rto'];
		$types = ['whatsapp', 'email'];

		foreach ($statuses as $status) {
			foreach ($types as $type) {
				ShippingNotification::firstOrCreate([
					'seller_id' => $sellerId,
					'notification_type' => $type,
					'order_status' => $status,
				], [
					'enabled' => 1, // Default enabled
					'template' => $this->getDefaultTemplate($type, $status),
				]);
			}
		}
	}

	// Get default templates
	private function getDefaultTemplate($type, $status)
	{
		if ($type === 'whatsapp') {
			return match ($status) {
				'transit' => 'Your order {order_number} is in transit. AWB: {awb_number}',
				'out for delivery' => 'Your order {order_number} is out for delivery. AWB: {awb_number}',
				'delivered' => 'Your order {order_number} has been delivered successfully. AWB: {awb_number}',
				'rto' => 'Delivery attempt for order {order_number} was unsuccessful. AWB: {awb_number}',
				default => 'Order status update for {order_number}'
			};
		} else {
			return match ($status) {
				'transit' => 'Your order {order_number} is currently in transit.',
				'out for delivery' => 'Your order {order_number} is out for delivery and will arrive today.',
				'delivered' => 'Your order {order_number} has been delivered successfully.',
				'rto' => 'We attempted to deliver your order {order_number} but were unsuccessful.',
				default => 'Order status update'
			};
		}
	}

}
