<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Events\ChatEvent;

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

Route::middleware('auth:web')->group(function() {
	Route::get('/chat', function() {
		header('Origin: ' . urlencode(env('WEBSOCKET_URL')));
		return view('chat');
	})->name('chat');
	Route::post('/chat', function() {
		$room = request()->post('room');
		$name = request()->post('name');
		$message = request()->post('message');
		
		if(!preg_match('/^\\w+$/', $room)) return 'Room format error';
		if(!preg_match('/^\\w+$/', $name)) return 'Name format error';
		if(!$message) return 'Message cannot be empty';
		
		broadcast(new ChatEvent($room, compact('name', 'message')));
		
		return 'Pushed to queue';
	});
});