<?php

use Illuminate\Support\Facades\Cache;
use App\Events\AttrUpdateEvent;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


// Delete
Route::get('/conn', function () {
    Cache::put('object', ['subscribe' => 'XBTUSD'], 5);
});

Route::get('/conn2', function () {
    event(new AttrUpdateEvent([]));
});


Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

// Get execution for a specific signal id. Called from execution.vue
Route::get('/getexecution/{id}', 'API\ExecutionController@getExecution');

/**
 * For vue router routes. Vue routes must have the opportunity to be reloaded
 * @see https://www.youtube.com/watch?v=z6yH6iB76nc&list=PLB4AdipoHpxaHDLIaMdtro1eXnQtl_UvE&index=7
 */
Route::get('{zzz}', "HomeController@index")->where( 'path', '([A-z\d-\/_.]+)?' );

// Signal execution. Called from Signal.vue and Execution.vue
Route::post('/exec', 'API\ExecutionController@executeSymbol');

// Repeat failed signal for a specific client. Called only from Execution.vue
Route::post('/repeatsignal', 'API\ExecutionController@repeatSignal');

// ..
Route::post('/execclose', 'API\ExecutionController@closeSymbol');

// Clear jobs and failed_jobs tables. Called from Execution.vue
Route::post('/clearjobs', 'API\ExecutionController@clearJobTables');


// Client validation. Called from client.vue
Route::post('/validateclient', 'API\ClientController@validateClient');

// Client check box selection. Called from client.vue
Route::post('/activateclient', 'API\ClientController@activateClient');

// Get client trading balance. Called from client.vue
Route::post('/gettradebalance', 'API\ClientController@getClientTradingBalance');

// Get client trading balance. Called from client.vue
Route::post('/dropbalance', 'API\ClientController@dropClientTradingBalance');











