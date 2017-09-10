<?php
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
Route::post('/user/auth', "Api\\UserController@auth");
Route::post('/category/get', "Api\\CategoryController@get");
Route::post('/category/create', "Api\\CategoryController@create");
Route::post('/category/update', "Api\\CategoryController@update");
Route::post('/category/delete', "Api\\CategoryController@delete");
Route::post('/good/get', "Api\\GoodController@get");
Route::post('/good/create', "Api\\GoodController@create");
Route::post('/good/update', "Api\\GoodController@update");
Route::post('/good/delete', "Api\\GoodController@delete");