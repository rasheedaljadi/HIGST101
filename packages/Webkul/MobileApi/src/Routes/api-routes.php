<?php

use Illuminate\Support\Facades\Route;
use Webkul\MobileApi\Http\Controllers\GraphQLController;

Route::group(['middleware' => ['web', 'storefront.key']], function () {
    Route::post('graphql', [GraphQLController::class, 'handle'])->name('mobile.graphql');
    Route::get('graphql', [GraphQLController::class, 'handle'])->name('mobile.graphql.get');
});
