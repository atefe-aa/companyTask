<?php

use Illuminate\Support\Fecades\Route;

Route::get('login',[AuthController::class,'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::group(['middleware' => ['role:administrator']], function () {
        Route::apiResource('company',CompanyController::class);
        Route::apiResource('employee',EmployeeController::class);
    });
});
