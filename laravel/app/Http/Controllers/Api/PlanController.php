<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class PlanController extends Controller
{
    /**
     * List available plans.
     */
    public function index(): JsonResponse
    {
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get()
            ->map(fn($p) => [
                'id'               => $p->id,
                'name'             => $p->name,
                'slug'             => $p->slug,
                'tier'             => $p->tier,
                'price_monthly_cny' => $p->price_monthly_cny,
                'price_yearly_cny'  => $p->price_yearly_cny,
                'features'         => $p->features,
            ]);

        return response()->json(['data' => $plans]);
    }

    /**
     * Get current user's membership.
     */
    public function myMembership(Request $request): JsonResponse
    {
        $membership = $request->user()->membership()
            ->with('plan')
            ->where('status', 'active')
            ->first();

        if (!$membership) {
            // Default to free tier
            $freePlan = Plan::where('tier', 'free')->first();
            return response()->json([
                'data' => [
                    'plan' => $freePlan?->only(['name', 'tier', 'features']),
                    'status' => 'free',
                ],
            ]);
        }

        return response()->json([
            'data' => [
                'plan'       => $membership->plan->only(['name', 'tier', 'features']),
                'status'     => $membership->status,
                'expires_at' => $membership->expires_at,
                'auto_renew' => $membership->auto_renew,
            ],
        ]);
    }

    /**
     * Create an order (initiate payment).
     */
    public function createOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'plan_id'        => 'required|exists:plans,id',
            'billing_cycle'  => 'required|in:monthly,yearly',
            'payment_method' => 'required|in:wechat,alipay,stripe',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $plan = Plan::findOrFail($request->plan_id);

        $amount = $request->billing_cycle === 'yearly'
            ? $plan->price_yearly_cny
            : $plan->price_monthly_cny;

        if ($amount <= 0) {
            return response()->json(['error' => 'invalid_plan_price', 'message' => 'Invalid plan price'], 400);
        }

        $order = Order::create([
            'user_id'        => $user->id,
            'plan_id'        => $plan->id,
            'order_no'       => 'AI' . date('YmdHis') . str_pad($user->id, 6, '0', STR_PAD_LEFT),
            'payment_method' => $request->payment_method,
            'amount_cny'     => $amount,
            'status'         => 'pending',
        ]);

        return response()->json(['data' => $order], 201);
    }
}
