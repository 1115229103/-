<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Work;
use App\Models\Order;
use App\Models\ModelRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'total_users'    => User::count(),
                'total_works'    => Work::count(),
                'total_models'   => ModelRegistry::count(),
                'total_revenue'  => Order::where('status', 'paid')->sum('amount_cny'),
                'today_works'    => Work::whereDate('created_at', today())->count(),
            ],
        ]);
    }
}
