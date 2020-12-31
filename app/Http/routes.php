<?php

ini_set('display_errors', 'On');

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::group(['prefix' => '/product'], function() {
	Route::post('/list', 'ProductController@prodlist');
	Route::post('/listcat', 'ProductController@prodlistcat');
	Route::post('/category', 'ProductController@catlist');
	Route::any('/detail', 'ProductController@anyprodetail');
});

	Route::group(['prefix'=>'/order'], function(){
	Route::post('/submit','OrderController@Ordersubmit');
	Route::post('/detail','OrderController@Orderdetail');
	Route::post('/history','OrderController@Orderhistory');
	Route::post('/invoice','OrderController@Orderdetailinvoice');
});

Route::group(['prefix' => '/tracking'], function() {
	Route::post('/list', 'TrackingController@tracklist');
	Route::post('/detail', 'TrackingController@trackdetail');
});

Route::group(['prefix' => '/member'], function() {
	Route::post('/detail', 'MembershipController@memdetail');
	Route::post('/update', 'MembershipController@memupdate');
	Route::post('/register', 'MembershipController@memregister');
	Route::post('/validation', 'MembershipController@memvalidation');
	Route::post('/idupload', 'MembershipController@memuploadid');
	Route::post('/createpwd', 'MembershipController@memcreatepwd');
	Route::post('/login', 'MembershipController@memlogin');
	Route::post('/updatepwd', 'MembershipController@memupdatepwd');
	Route::post('/newsletter', 'MembershipController@memnewsletter');
});

Route::group(['prefix' => '/balance'], function() {
	Route::post('/topup', 'BalanceController@balancetopup');
	Route::post('/deduct', 'BalanceController@balancededuct');
	Route::post('/info', 'BalanceController@balanceinfo');
	Route::post('/history', 'BalanceController@balancehistory');
	Route::post('/bonus', 'BalanceController@balancebonus');
	Route::post('/package', 'BalanceController@balancepackage');
});

Route::group(['prefix'=>'/nearest'],function(){
	Route::post('/list','NearestController@nearestlist');
});

Route::group(['prefix'=>'/payment'],function(){
	Route::post('/mobile','PaymentController@MobilePayment');
	Route::post('/confirm','PaymentController@paymentconfirm');
	Route::post('/transf','PaymentController@paymentTransf');
	Route::get('/doku','PaymentController@doku');		
	Route::post('/dokucc', 'PaymentController@dokucc');	
});

Route::group(['prefix'=>'/simulator'],function(){
	Route::get('/request_token','SimulatorController@requestToken');	
	Route::get('/sign_on','SimulatorController@signOn');
});

Route::group(['prefix'=>'/pickup'],function(){
	Route::post('/submit','PickupController@pickupsubmit');
	Route::post('/history','PickupController@pickuphistory');
	Route::post('/calc','PickupController@pickupcalc');
	Route::post('/detail','PickupController@pickupdetail');
	Route::post('/invdetail','PickupController@pickupinvoicedetail');
});

Route::group(['prefix'=>'/oto'],function(){
	Route::post('/category','OtoController@category');
	Route::post('/product','OtoController@product');
	Route::post('/submit','OtoController@Ordersubmit');
	Route::post('/history','OtoController@Orderhistory');
	Route::post('/detail','OtoController@Orderdetail');
});

Route::group(['prefix'=>'/return'],function(){
	Route::post('/submit','MerchantreturnController@Merchantsubmit');
});

Route::group(['prefix'=>'/locker'],function(){
	Route::post('/search','NearestController@nearestsearch');
});

Route::group(['prefix'=>'/merchant'],function(){
	Route::post('/pickup','MerchantController@pickup');
	Route::post('/copickup','MerchantController@cashonpickup');
});

Route::group(['prefix'=>'/cop'],function(){
	Route::post('/detail','CashonpickupController@OrderDetail');
	Route::post('/submit','CashonpickupController@OrderSubmit');
});


Route::group(['prefix'=>'/partner', 'middleware' => 'access.token'], function() {
	Route::post('/orders', 'PartnerController@saveOrders');
//	Route::post('/orderdebug/{orderId}', 'PartnerController@_lockerCanceled');
	Route::post('/status', 'PartnerController@updateStatus');
	Route::post('/delivery', 'PartnerController@updateDelivery');
});

Route::group(['prefix'=>'/locker', 'middleware' => 'access.token'],function(){
	Route::post('/location','LockerController@getLocation');
	Route::post('/country','LockerController@getCountry');
	Route::post('/province','LockerController@getProvince');
	Route::post('/city','LockerController@getCity');
});

Route::group(['prefix'=>'/parcel', 'middleware' => 'access.token'],function(){
	Route::post('/status','ParcelController@parcelstatus');
});
