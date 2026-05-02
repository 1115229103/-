<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class OrderController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Order::with(['user', 'plan'])->latest()->paginate(20)]);
    }
}
