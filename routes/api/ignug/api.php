<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Ignug\CatalogueController;
use App\Http\Controllers\Ignug\ImageController;
use App\Http\Controllers\Ignug\TeacherController;
use App\Http\Controllers\Ignug\InstitutionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::apiResource('catalogues', CatalogueController::class);

Route::group(['prefix' => 'images'], function () {
    Route::get('avatars', [ImageController::class, 'getAvatar']);
    Route::post('avatars', [ImageController::class, 'createAvatar']);
});

Route::group(['prefix' => 'teachers'], function () {
    Route::post('upload_image', [TeacherController::class, 'uploadImage']);
});

Route::group(['prefix' => 'institutions'], function () {
    Route::post('assign_institution', [InstitutionController::class, 'assignInstitution']);
    Route::post('remove_institution', [InstitutionController::class, 'removeInstitution']);
});
