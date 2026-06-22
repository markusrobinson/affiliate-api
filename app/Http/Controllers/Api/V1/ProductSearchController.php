<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ProductSearchRequest;
use App\Models\Team;
use App\Services\ProductSearchService;
use Illuminate\Http\JsonResponse;

class ProductSearchController extends Controller
{
    public function __invoke(ProductSearchRequest $request, ProductSearchService $service): JsonResponse
    {
        /** @var Team $team */
        $team = $request->user();
        $query = $request->validated('q');

        $result = $service->search($team, $query);

        return response()->json([
            'data' => array_map(fn ($product) => $product->toArray(), $result->products),
            'meta' => [
                'query' => $query,
                'total' => count($result->products),
                'providers_queried' => $result->providersQueried,
                'providers_failed' => $result->providersFailed,
                'cached' => $result->wasCached,
            ],
        ]);
    }
}
