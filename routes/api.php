<?php

use App\Http\Controllers\FetchEmailsController;
use App\Http\Controllers\SearchEmailsController;
use App\Http\Controllers\SendEmailController;
use App\Http\Controllers\ViewEmailController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::group(['prefix' => 'emails', 'middleware' => ['auth:api']], function () {
    Route::post('/', SendEmailController::class)->name('emails.send');
    Route::get('/', FetchEmailsController::class)->name('emails.index');
    Route::get('search', SearchEmailsController::class)->name('emails.search');
    Route::get('/{email}', ViewEmailController::class)->name('api.emails.show');
});
