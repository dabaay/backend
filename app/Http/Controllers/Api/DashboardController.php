<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Sale;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Models\Supplier;
use App\Models\Purchase;
use App\Models\Expense;
use App\Models\Debt;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats()
    {
        $today = Carbon::today();
        
        // Basic Summary (Today's performance)
        $todaySales = Sale::whereDate('sale_date', $today)->sum('total_amount');
        $yesterdaySales = Sale::whereDate('sale_date', Carbon::yesterday())->sum('total_amount');
        
        $newCustomers = Customer::whereDate('registration_date', $today)->count();
        $totalCustomers = Customer::count();
        
        $lowStockCount = Product::where('current_stock', '<=', DB::raw('minimum_stock'))->count();
        
        // System-wide Financial Overview
        $totalRevenue = Sale::sum('total_amount');
        $totalCost = DB::table('sale_items')->sum(DB::raw('quantity * cost_price'));
        $estimatedProfit = $totalRevenue - $totalCost;
        
        $inventoryValue = Product::sum(DB::raw('current_stock * cost_price'));
        $totalDebt = Debt::where('status', '!=', 'paid')->where('status', '!=', 'written_off')->sum('remaining_amount');

        // Module Counters
        $moduleCounts = [
            'users' => User::count(),
            'suppliers' => Supplier::count(),
            'products' => Product::count(),
            'customers' => $totalCustomers,
            'sales' => Sale::count(),
            'purchases' => Purchase::count(),
            'expenses' => Expense::count(),
            'debts' => Debt::count(),
        ];
        
        $recentSales = Sale::with('customer')->latest()->take(5)->get();
        
        // Top Products by sales frequency (actually joined with sale_items for real data)
        $topProducts = DB::table('sale_items')
            ->select('products.name', 'products.current_stock', 'products.unit', DB::raw('SUM(sale_items.quantity) as total_sold'))
            ->join('products', 'sale_items.product_id', '=', 'products.product_id')
            ->groupBy('products.product_id', 'products.name', 'products.current_stock', 'products.unit')
            ->orderByDesc('total_sold')
            ->take(5)
            ->get();

        // 1. Sales Trend (Last 30 Days)
        $salesTrend = DB::table('sales')
            ->select(DB::raw('DATE(sale_date) as date'), DB::raw('SUM(total_amount) as total'))
            ->where('sale_date', '>=', Carbon::now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 2. Payment Methods Distribution
        $paymentMethods = DB::table('sales')
            ->select('payment_method as name', DB::raw('COUNT(*) as value'))
            ->groupBy('payment_method')
            ->get();

        // 3. Expense Breakdown by Category
        $expenseBreakdown = DB::table('expenses')
            ->select('expense_category as name', DB::raw('SUM(amount) as value'))
            ->where('status', 'approved')
            ->groupBy('expense_category')
            ->get();

        // 4. Sales vs Expenses Trend (Last 30 Days)
        $salesVsExpenses = DB::table('sales')
            ->select(DB::raw('DATE(sale_date) as date'), DB::raw('SUM(total_amount) as sales'))
            ->where('sale_date', '>=', Carbon::now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function($item) {
                $expenseAmount = DB::table('expenses')
                    ->whereDate('expense_date', $item->date)
                    ->where('status', 'approved')
                    ->sum('amount');
                $item->expenses = $expenseAmount;
                return $item;
            });

        // 5. Customer Growth Trend (Last 90 Days)
        $customerGrowth = DB::table('customers')
            ->select(DB::raw('DATE(registration_date) as date'), DB::raw('COUNT(*) as count'))
            ->where('registration_date', '>=', Carbon::now()->subDays(90))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 6. Inventory Status Distribution
        $inventoryStatus = [
            ['name' => 'Low Stock', 'value' => Product::whereRaw('current_stock <= minimum_stock')->count()],
            ['name' => 'Adequate', 'value' => Product::whereRaw('current_stock > minimum_stock AND current_stock < maximum_stock')->count()],
            ['name' => 'Overstocked', 'value' => Product::whereRaw('current_stock >= maximum_stock')->count()],
            ['name' => 'Out of Stock', 'value' => Product::where('current_stock', 0)->count()],
        ];

        // 7. Top Customers by Revenue
        $topCustomers = DB::table('sales')
            ->select('customers.full_name as name', DB::raw('SUM(sales.total_amount) as total'))
            ->join('customers', 'sales.customer_id', '=', 'customers.customer_id')
            ->groupBy('customers.customer_id', 'customers.full_name')
            ->orderByDesc('total')
            ->take(10)
            ->get();

        // 8. Debt Status Overview
        $debtStatus = DB::table('debts')
            ->select('status as name', DB::raw('COUNT(*) as value'))
            ->groupBy('status')
            ->get();

        // 9. Monthly Revenue Comparison (Last 6 Months)
        $monthlyRevenue = DB::table('sales')
            ->select(
                DB::raw('DATE_FORMAT(sale_date, "%Y-%m") as month'),
                DB::raw('SUM(total_amount) as revenue'),
                DB::raw('COUNT(*) as transactions')
            )
            ->where('sale_date', '>=', Carbon::now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // 10. Profit Margin Trend (Last 30 Days)
        $profitTrend = DB::table('sales')
            ->select(DB::raw('DATE(sale_date) as date'), DB::raw('SUM(total_amount) as revenue'))
            ->where('sale_date', '>=', Carbon::now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function($item) {
                $cost = DB::table('sale_items')
                    ->join('sales', 'sale_items.sale_id', '=', 'sales.sale_id')
                    ->whereDate('sales.sale_date', $item->date)
                    ->sum(DB::raw('sale_items.quantity * sale_items.cost_price'));
                
                $profit = $item->revenue - $cost;
                $item->profit = $profit;
                $item->margin = $item->revenue > 0 ? round(($profit / $item->revenue) * 100, 2) : 0;
                return $item;
            });

        // 11. Payment Method Trends (Last 30 Days)
        $paymentTrends = DB::table('sales')
            ->select(
                DB::raw('DATE(sale_date) as date'),
                'payment_method',
                DB::raw('SUM(total_amount) as amount')
            )
            ->where('sale_date', '>=', Carbon::now()->subDays(30))
            ->groupBy('date', 'payment_method')
            ->orderBy('date')
            ->get()
            ->groupBy('date')
            ->map(function($items, $date) {
                $result = ['date' => $date];
                foreach($items as $item) {
                    $result[$item->payment_method] = $item->amount;
                }
                return $result;
            })
            ->values();

        // 12. Product Category Sales
        $categorySales = DB::table('sale_items')
            ->select('products.category as name', DB::raw('SUM(sale_items.subtotal) as value'))
            ->join('products', 'sale_items.product_id', '=', 'products.product_id')
            ->whereNotNull('products.category')
            ->groupBy('products.category')
            ->orderByDesc('value')
            ->take(10)
            ->get();

        return response()->json([
            'summary' => [
                'today_sales' => $todaySales,
                'yesterday_sales' => $yesterdaySales,
                'new_customers' => $newCustomers,
                'total_customers' => $totalCustomers,
                'low_stock_count' => $lowStockCount,
            ],
            'system_overview' => [
                'total_revenue' => $totalRevenue,
                'estimated_profit' => $estimatedProfit,
                'inventory_value' => $inventoryValue,
                'total_outstanding_debt' => $totalDebt,
            ],
            'module_counts' => $moduleCounts,
            'recent_activity' => $recentSales,
            'top_products' => $topProducts,
            'salesTrend' => $salesTrend,
            'paymentMethods' => $paymentMethods,
            'expenseBreakdown' => $expenseBreakdown,
            'salesVsExpenses' => $salesVsExpenses,
            'customerGrowth' => $customerGrowth,
            'inventoryStatus' => $inventoryStatus,
            'topCustomers' => $topCustomers,
            'debtStatus' => $debtStatus,
            'monthlyRevenue' => $monthlyRevenue,
            'profitTrend' => $profitTrend,
            'paymentTrends' => $paymentTrends,
            'categorySales' => $categorySales,
        ]);
    }
}
