<?php

namespace App\Http\Controllers\Admin;

use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PlanController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Plan::orderBy('sort_order')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:128',
            'slug' => 'required|string|max:64|unique:plans,slug',
            'tier' => 'required|string|max:32',
            'price_monthly_cny' => 'nullable|numeric',
            'price_yearly_cny' => 'nullable|numeric',
            'features' => 'nullable|array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);
        $plan = Plan::create($data);
        return response()->json(['data' => $plan], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $plan = Plan::findOrFail($id);
        $data = $request->validate([
            'name' => 'string|max:128',
            'slug' => 'string|max:64|unique:plans,slug,' . $id,
            'tier' => 'string|max:32',
            'price_monthly_cny' => 'nullable|numeric',
            'price_yearly_cny' => 'nullable|numeric',
            'features' => 'nullable|array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);
        $plan->update($data);
        return response()->json(['data' => $plan]);
    }

    public function destroy(int $id): JsonResponse
    {
        Plan::findOrFail($id)->delete();
        return response()->json(['data' => null]);
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $plan = Plan::findOrFail($id);
        $plan->update(['is_active' => !$plan->is_active]);
        return response()->json(['data' => $plan]);
    }
}
