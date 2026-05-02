<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
    public function report(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);

        $revenue = Order::where('status', 'paid')
            ->where('paid_at', '>=', now()->subDays($days))
            ->select(DB::raw('DATE(paid_at) as date'), DB::raw('SUM(amount_cny) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'data' => [
                'revenue_by_day' => $revenue,
                'total_revenue'  => $revenue->sum('total'),
                'total_orders'   => Order::where('status', 'paid')->count(),
                'pending_orders' => Order::where('status', 'pending')->count(),
            ],
        ]);
    }
}
