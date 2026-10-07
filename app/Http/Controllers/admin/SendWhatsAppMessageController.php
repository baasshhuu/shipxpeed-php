<?php

namespace App\Http\Controllers\admin;

use App\Models\Brand;
use App\Models\Cms;
use App\Helper\Helper;
use App\Models\Testimonial;
use Illuminate\View\View;
use Illuminate\Http\Request;
use \Yajra\Datatables\Datatables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Controller;
use App\Models\PriceSetting;
use App\Models\SellerList;
use App\Models\LogisticProvider;
use App\Models\SendWhatsAppMessage;
use App\Models\ShippingNotification;
use App\Models\Recharge;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;


class SendWhatsAppMessageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('admin.SendWhatsAppMessage.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.SendWhatsAppMessage.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'number' => 'required|string|max:15',
            ]);

            // Get the number from request
            $inputNumber = $request->input('number');
            
            if (empty($inputNumber)) {
                return redirect()->back()->with('error', 'Phone number is required!');
            }

            // Clean the phone number
            $number = preg_replace('/[^0-9+]/', '', $inputNumber);
            
            // Add +91 if not present
            if (!str_starts_with($number, '+')) {
                $number = '+91' . ltrim($number, '+91');
            }

            // Check if number already exists
            $exists = SendWhatsAppMessage::where('number', $number)->first();
            if ($exists) {
                return redirect()->back()->with('error', 'Phone number already exists!');
            }

            // Create the record
            $message = SendWhatsAppMessage::create([
                'number' => $number,
            ]);

            if ($message) {
                return redirect()->back()->with('success', 'Phone number added successfully!');
            } else {
                return redirect()->back()->with('error', 'Failed to save phone number!');
            }

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Upload Excel file with phone numbers
     */
    public function uploadExcel(Request $request)
    {
        try {
            // Validate file with more permissive rules
            $validator = Validator::make($request->all(), [
                'excel_file' => 'required|file|max:2048',
            ]);
            
            if ($validator->fails()) {
                return redirect()->back()->with('error', 'Please select a valid file (max 2MB).');
            }

            $file = $request->file('excel_file');
            
            // Check file extension manually
            $allowedExtensions = ['xlsx', 'xls', 'csv'];
            $fileExtension = strtolower($file->getClientOriginalExtension());
            
            if (!in_array($fileExtension, $allowedExtensions)) {
                return redirect()->back()->with('error', 'Please upload only Excel files (.xlsx, .xls) or CSV files (.csv).');
            }
            
            // Debug information
            if (!$file) {
                return redirect()->back()->with('error', 'No file was uploaded.');
            }
            
            // Log file details for debugging
            \Log::info('File upload details:', [
                'name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
                'size' => $file->getSize()
            ]);
            
            // Check if file is valid
            if (!$file->isValid()) {
                return redirect()->back()->with('error', 'The uploaded file is invalid.');
            }
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            $added = 0;
            $skipped = 0;

            foreach ($rows as $key => $row) {
                // Skip header row
                if ($key == 0) continue;
                
                // Get phone number from first column
                $number = trim($row[0]);
                
                if (empty($number)) {
                    $skipped++;
                    continue;
                }

                // Clean the phone number
                $number = preg_replace('/[^0-9+]/', '', $number);
                
                // Add +91 if not present
                if (!str_starts_with($number, '+')) {
                    $number = '+91' . ltrim($number, '+91');
                }

                // Check if number already exists
                $exists = SendWhatsAppMessage::where('number', $number)->exists();
                if (!$exists) {
                    SendWhatsAppMessage::create([
                        'number' => $number,
                    ]);
                    $added++;
                } else {
                    $skipped++;
                }
            }

            return redirect()->back()->with('success', "Excel uploaded successfully! Added: $added numbers, Skipped: $skipped numbers");

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Get all numbers for DataTable
     */
    public function getNumbers(Request $request)
    {
        if ($request->ajax()) {
            $data = SendWhatsAppMessage::select('id', 'number', 'created_at')->latest();
            
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function($row) {
                    return '<button class="btn btn-danger btn-sm delete-btn" data-id="'.$row->id.'">
                                <i class="fas fa-trash"></i>
                            </button>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $message = SendWhatsAppMessage::findOrFail($id);
            $message->delete();

            return response()->json([
                'success' => true,
                'message' => 'Phone number deleted successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send WhatsApp message to all numbers
     */
    public function sendMessage(Request $request)
    {
        try {
            $request->validate([
                'message' => 'required|string',
            ]);

            $numbers = SendWhatsAppMessage::all();
            
            if ($numbers->isEmpty()) {
                return redirect()->back()->with('error', 'No phone numbers found to send messages!');
            }

            $sent = 0;
            $failed = 0;

            foreach ($numbers as $numberRecord) {
                try {
                    $this->sendBulkWhatsAppMessage($numberRecord->number, $request->message);
                    $sent++;
                } catch (\Exception $e) {
                    $failed++;
                }
            }

            return redirect()->back()->with('success', "Messages sent! Success: $sent, Failed: $failed");

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Private function to send bulk WhatsApp message
     */
    private function sendBulkWhatsAppMessage($phoneNumber, $message)
    {
        try {
            // Ensure phone number starts with +91
            if (!str_starts_with($phoneNumber, '+')) {
                $phoneNumber = '+91' . ltrim($phoneNumber, '+91');
            }


            $payload = [
                "apiKey" => "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6IjY4OTMwNmIxNTk2NTBhMGMwYmEyNmM1NyIsIm5hbWUiOiJTSElQWFBFRUQgTE9HSVNUSUNTICIsImFwcE5hbWUiOiJBaVNlbnN5IiwiY2xpZW50SWQiOiI2ODkzMDZiMTU5NjUwYTBjMGJhMjZjNTIiLCJhY3RpdmVQbGFuIjoiRlJFRV9GT1JFVkVSIiwiaWF0IjoxNzU0NDY1OTY5fQ.KrQxCgEKxGJLJ6KCLk6vmNrmakhhqnM19ycdHjskf84",
                "campaignName" => "marketingfor",
                "destination" => $phoneNumber,
                "userName" => "Customer",
                "templateParams" => ["22", "50"]
            ];
// dd($payload);
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post('https://backend.aisensy.com/campaign/t1/api/v2', $payload);
        //   dd($response->body());
            if ($response->successful()) {
                Log::info("✔ WhatsApp bulk message sent to {$phoneNumber}");
    
            } else {
                Log::warning("✖ Failed to send WhatsApp message to {$phoneNumber} - HTTP {$response->status()}");
                throw new \Exception("Failed to send message");
            }

        } catch (\Exception $e) {
            Log::error("❌ WhatsApp bulk message error for {$phoneNumber}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Private function to send WhatsApp message
     */


    /**
     * Download sample Excel format
     */
    public function downloadSampleExcel()
    {
        try {
            // Create CSV content instead of Excel for simplicity
            $csvContent = "Phone Number\n";
            $csvContent .= "9876543210\n";
            $csvContent .= "8765432109\n";
            $csvContent .= "7654321098\n";
            $csvContent .= "9123456789\n";
            $csvContent .= "8012345678\n";

            $fileName = 'whatsapp_numbers_sample_' . date('Y-m-d_H-i-s') . '.csv';

            return response($csvContent, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error downloading sample file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get WhatsApp campaign data based on status
     */
  
}
