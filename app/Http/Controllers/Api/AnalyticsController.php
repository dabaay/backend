<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Expense;
use App\Models\Debt;
use App\Models\DebtPayment;

class AnalyticsController extends Controller
{
    private function getDateRange(Request $request, $defaultDays = 30)
    {
        $startDate = $request->query('start_date') 
            ? Carbon::parse($request->query('start_date'))->startOfDay() 
            : Carbon::now()->subDays($defaultDays)->startOfDay();
            
        $endDate = $request->query('end_date') 
            ? Carbon::parse($request->query('end_date'))->endOfDay() 
            : Carbon::now()->endOfDay();
            
        return [$startDate, $endDate];
    }

    public function dashboard(Request $request)
    {
        list($startDate, $endDate) = $this->getDateRange($request);

        // 1. Sales Trend
        $salesTrend = DB::table('sales')
            ->select(DB::raw('DATE(sale_date) as date'), DB::raw('SUM(total_amount) as total'))
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 2. Payment Methods Distribution
        $paymentMethods = DB::table('sales')
            ->select('payment_method as name', DB::raw('COUNT(*) as value'))
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->groupBy('payment_method')
            ->get();

        return response()->json([
            'salesTrend' => $salesTrend,
            'paymentMethods' => $paymentMethods
        ]);
    }

    public function products(Request $request)
    {
        list($startDate, $endDate) = $this->getDateRange($request);

        // 1. Stock by Category (Real-time, not date-dependent usually, but we keep it consistent)
        $stockByCategory = DB::table('products')
            ->select('category as name', DB::raw('SUM(current_stock) as value'))
            ->groupBy('category')
            ->get();

        // 2. Revenue by Category
        $revenueByCategory = DB::table('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.product_id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.sale_id')
            ->select('products.category as name', DB::raw('SUM(sale_items.subtotal) as value'))
            ->whereBetween('sales.sale_date', [$startDate, $endDate])
            ->groupBy('products.category')
            ->get();

        return response()->json([
            'stockByCategory' => $stockByCategory,
            'revenueByCategory' => $revenueByCategory
        ]);
    }

    public function customers(Request $request)
    {
        list($startDate, $endDate) = $this->getDateRange($request, 180); // Default 6 months

        // 1. Growth Trend
        $growthTrend = DB::table('customers')
            ->select(DB::raw("DATE_FORMAT(registration_date, '%Y-%m') as month"), DB::raw('COUNT(*) as total'))
            ->whereBetween('registration_date', [$startDate, $endDate])
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // 2. Debt vs Credit Limit (Current snapshot)
        $debtStatus = DB::table('customers')
            ->select(
                DB::raw("CASE 
                    WHEN current_balance = 0 THEN 'No Debt' 
                    WHEN current_balance < 100 THEN '< $100' 
                    WHEN current_balance < 500 THEN '$100 - $500' 
                    ELSE '> $500' 
                END as bucket"),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('bucket')
            ->get();

        return response()->json([
            'growthTrend' => $growthTrend,
            'debtStatus' => $debtStatus
        ]);
    }

    public function walpo(Request $request)
    {
        list($startDate, $endDate) = $this->getDateRange($request);

        // 1. Credit Issued vs Recovery
        $dates = DB::table('sales')
            ->select(DB::raw('DATE(sale_date) as date'))
            ->where('payment_status', 'credit')
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->union(
                DB::table('debt_payments')
                    ->select(DB::raw('DATE(payment_date) as date'))
                    ->whereBetween('payment_date', [$startDate, $endDate])
            )
            ->get();

        $data = [];
        foreach ($dates as $dateObj) {
            $date = $dateObj->date;
            
            $issued = DB::table('sales')
                ->where('payment_status', 'credit')
                ->whereDate('sale_date', $date)
                ->sum('total_amount');

            $recovered = DB::table('debt_payments')
                ->whereDate('payment_date', $date)
                ->sum('amount_paid');

            $data[] = [
                'date' => $date,
                'issued' => (float)$issued,
                'recovered' => (float)$recovered
            ];
        }

        // Sort by date
        usort($data, function($a, $b) {
            return strcmp($a['date'], $b['date']);
        });

        return response()->json($data);
    }

    public function expenses(Request $request)
    {
        list($startDate, $endDate) = $this->getDateRange($request, 180);

        // 1. Monthly Burn
        $monthlyBurn = DB::table('expenses')
            ->select(DB::raw("DATE_FORMAT(expense_date, '%Y-%m') as month"), DB::raw('SUM(amount) as total'))
            ->where('status', 'approved')
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // 2. Category Breakdown
        $categoryBreakdown = DB::table('expenses')
            ->select('expense_category as name', DB::raw('SUM(amount) as value'))
            ->where('status', 'approved')
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->groupBy('expense_category')
            ->get();

        return response()->json([
            'monthlyBurn' => $monthlyBurn,
            'categoryBreakdown' => $categoryBreakdown
        ]);
    }

}
