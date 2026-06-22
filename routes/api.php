<?php

use App\Http\Controllers\Api\V1\ProductSearchController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {
    Route::get('products/search', ProductSearchController::class)->name('api.v1.products.search');
});
