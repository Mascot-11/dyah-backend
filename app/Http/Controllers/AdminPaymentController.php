<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdminPayment;
use Carbon\Carbon;

class AdminPaymentController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'month');

        $query = AdminPayment::query();

        if ($filter === '3months') {
            $query->where('payment_date', '>=', Carbon::now()->subMonths(3));
        } elseif ($filter === '6months') {
            $query->where('payment_date', '>=', Carbon::now()->subMonths(6));
        } else {
            $query->whereMonth('payment_date', Carbon::now()->month)
                  ->whereYear('payment_date', Carbon::now()->year);
        }

        $adminPayments = $query->orderBy('payment_date', 'desc')->get();

        // Return JSON data, NOT a Blade view
        return response()->json($adminPayments);
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_type' => 'required|in:online,instagram,facebook,tiktok,walk-in',
            'payment_date' => 'required|date',
            'payment_time' => 'required',
            'payment_method' => 'required|in:cash,online',
            'amount' => 'required|numeric|min:0',
        ]);

        $payment = AdminPayment::create($request->all());

        // Return JSON response after successful creation
        return response()->json([
            'message' => 'Payment added successfully.',
            'payment' => $payment
        ], 201);
    }
}
