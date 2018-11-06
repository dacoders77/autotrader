<?php

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

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

/**
 * For vue router routes. Vue routes must have the opportunity to be reloaded
 * @see https://www.youtube.com/watch?v=z6yH6iB76nc&list=PLB4AdipoHpxaHDLIaMdtro1eXnQtl_UvE&index=7
 */
Route::get('{zzz}', "HomeController@index")->where( 'path', '([A-z\d-\/_.]+)?' );

// Test symbol exec controller. DELETE
Route::post('/exec', 'SymbolController@executeSymbol');
