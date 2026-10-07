<?php

namespace App\Http\Controllers\Investor;

use Illuminate\Support\Facades\Session;
use Barryvdh\DomPDF\Facade\Pdf;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\RateCard;
use App\Models\Recharge;
use App\Models\SellerAgreement;
use App\Models\SellerBankDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\{Order, Buyer, OrderItem, Warehouse, OrderPackageDetail, SellerList};
use App\Models\SellerAddress;
use App\Models\State;
use App\Models\City;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use App\Helper\Helper;
use App\Models\LogisticProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Excel as ExcelFormat;
use App\Imports\OrdersImport;

class InvestorController extends Controller
{
    /**
     * Show investor dashboard
     */
    public function dashboard()
    {
        // Get basic stats for dashboard
        $totalSellers = SellerList::count();
        $totalOrders = Order::count();
        $todayOrders = Order::whereDate('created_at', Carbon::today())->count();
        $last7DaysOrders = Order::where('created_at', '>=', Carbon::now()->subDays(7))->count();
        $lastMonthOrders = Order::where('created_at', '>=', Carbon::now()->subMonth())->count();
        
        // Recent sellers (last 10)
        $recentSellers = SellerList::with(['sellerAddress'])
            ->latest()
            ->take(10)
            ->get();
            
        // Recent orders (last 10)
        $recentOrders = Order::latest()
            ->take(10)
            ->get();

        return view('investor.dashboard', compact(
            'totalSellers', 
            'totalOrders', 
            'todayOrders', 
            'last7DaysOrders', 
            'lastMonthOrders',
            'recentSellers',
            'recentOrders'
        ));
    }

    /**
     * Show sellers page
     */
    public function sellers()
    {
        return view('investor.sellers');
    }

    /**
     * Show orders page
     */
    public function orders()
    {
        return view('investor.orders');
    }

    /**
     * Get sellers data with filters
     */
    public function getSellersData(Request $request)
    {
        $query = SellerList::with(['sellerAddress']);

        // Date filter
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Status filter
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $sellers = $query->latest()->paginate(20);

        // Get counts for stats
        $totalCount = SellerList::count();
        $filteredCount = $query->count();
        $todayCount = SellerList::whereDate('created_at', Carbon::today())->count();
        $weekCount = SellerList::where('created_at', '>=', Carbon::now()->subWeek())->count();
        $monthCount = SellerList::where('created_at', '>=', Carbon::now()->subMonth())->count();

        return response()->json([
            'sellers' => $sellers,
            'stats' => [
                'total' => $totalCount,
                'filtered' => $filteredCount,
                'today' => $todayCount,
                'week' => $weekCount,
                'month' => $monthCount
            ]
        ]);
    }

    /**
     * Get orders data with filters
     */
    public function getOrdersData(Request $request)
    {
        $query = Order::query();

        // Date filter
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Status filter
        if ($request->has('status') && $request->status !== '') {
            $query->where('order_status', $request->status);
        }

        // AWB filter
        if ($request->has('awb') && $request->awb) {
            $query->where('awb', 'like', '%' . $request->awb . '%');
        }

        $orders = $query->latest()->paginate(20);

        // Get counts for stats
        $totalCount = Order::count();
        $filteredCount = $query->count();
        $todayCount = Order::whereDate('created_at', Carbon::today())->count();
        $last7DaysCount = Order::where('created_at', '>=', Carbon::now()->subDays(7))->count();
        $lastMonthCount = Order::where('created_at', '>=', Carbon::now()->subMonth())->count();

        return response()->json([
            'orders' => $orders,
            'stats' => [
                'total' => $totalCount,
                'filtered' => $filteredCount,
                'today' => $todayCount,
                'last7Days' => $last7DaysCount,
                'lastMonth' => $lastMonthCount
            ]
        ]);
    }

    /**
     * Show profile page
     */
    public function profile()
    {
        return view('investor.profile');
    }

    /**
     * Update profile
     */
    public function updateProfile(Request $request)
    {
        $investor = Auth::guard('investor')->user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:255',
            'investment_amount' => 'required|numeric|min:0',
        ]);

        $investor->update($request->only([
            'name', 'phone', 'company_name', 'investment_amount'
        ]));

        Session::flash('success', 'Profile updated successfully.');
        return back();
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $investor = Auth::guard('investor')->user();
        
        if (!Hash::check($request->current_password, $investor->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $investor->update([
            'password' => Hash::make($request->password)
        ]);

        Session::flash('success', 'Password changed successfully.');
        return back();
    }
}
