<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\DB;
use Validator, Input, Redirect ;
use App\Http\Helpers\WebCurl;
use \PDF;
use Mail;

class PickupController extends Controller
{
	var $curl;

	public function __construct() {
		$headers    = ['Content-Type: application/json'];
		$this->curl = new WebCurl($headers);
	}

	// function from old app
	protected $headers = array (
		'Content-Type: application/json'
	);
	protected $is_post = 0;

public function post_data($url, $post_data = array(), $headers = array(), $options = array()) {
	$result = null;
	$curl = curl_init ();

	if ((is_array ( $options )) && count ( $options ) > 0) {
		$this->options = $options;
	}
	if ((is_array ( $headers )) && count ( $headers ) > 0) {
		$this->headers = $headers;
	}
	if ($this->is_post !== null) {
		$this->is_post = 1;
	}

	curl_setopt ( $curl, CURLOPT_URL, $url );
	curl_setopt ( $curl, CURLOPT_POST, $this->is_post );
	curl_setopt ( $curl, CURLOPT_POSTFIELDS, $post_data );
	curl_setopt ( $curl, CURLOPT_COOKIEJAR, "" );
	curl_setopt ( $curl, CURLOPT_FOLLOWLOCATION, true );
	curl_setopt ( $curl, CURLOPT_ENCODING, "" );
	curl_setopt ( $curl, CURLOPT_RETURNTRANSFER, true );
	curl_setopt ( $curl, CURLOPT_AUTOREFERER, true );
	curl_setopt ( $curl, CURLOPT_SSL_VERIFYPEER, false ); // required for https urls
	curl_setopt ( $curl, CURLOPT_CONNECTTIMEOUT, 5 );
	curl_setopt ( $curl, CURLOPT_TIMEOUT, 5 );
	curl_setopt ( $curl, CURLOPT_MAXREDIRS, 10 );
	curl_setopt ( $curl, CURLOPT_HTTPHEADER, $this->headers );

	$content = curl_exec ( $curl );
	$response = curl_getinfo ( $curl );
	$result = json_decode ( $content, TRUE );

	//debug


	curl_close ( $curl );
	return $result;
}

public function Pickupcalc(Request $req) {

	$notif = $this->_checkToken($req->json('token'));

	if( $req->input('phone') )
	{
		$phone = $req->json('phone');
		$pickup_address = $req->json('pickup_address');
		$pickup_address_detail = $req->json('pickup_address_detail');
		$pickup_address_name = $req->json('pickup_address_name');
		$pickup_address_phone = $req->json('pickup_address_phone');
		$pickup_address_lat = $req->json('pickup_address_lat');
		$pickup_address_long = $req->json('pickup_address_long');
		$pickup_locker_name = $req->json('pickup_locker_name');
		$pickup_locker_size = $req->json('pickup_locker_size');
		$pickup_date = $req->json('pickup_date');
		$pickup_time = $req->json('pickup_time');
		$item_description = $req->json('item_description');
		$recipient_name = $req->json('recipient_name');
		$recipient_phone = $req->json('recipient_phone');
		$recipient_address = $req->json('recipient_address');
		$recipient_address_detail = $req->json('recipient_address_detail');
		$recipient_address_lat = $req->json('recipient_address_lat');
		$recipient_address_long = $req->json('recipient_address_long');
		$recipient_locker_name=$req->json('recipient_locker_name');
		$recipient_locker_size=$req->json('recipient_locker_size');
		$item_photo_1 = $req->json('item_photo_1');
		$item_photo_2 = $req->json('item_photo_2');
		$item_photo_3 = $req->json('item_photo_3');
		$recipient_email=$req->json('recipient_email');

	} else {
		$notif->setDataError();
		return response()->json($notif->build());
	}

	$msg = 'Request : '.json_encode($req->input());
	$this->logData($msg);
	if($notif->isOK()) {

		//cek condition
		// locker to locker
		if ($pickup_address == "" && $recipient_address == "") {

			if ($pickup_locker_name == "" || $recipient_locker_name == "" ){
				$notif->setDataError();
				return response()->json($notif->build());
			}

			$sql_pickup_lock = "Select latitude, longitude from locker_locations
			where name like '%".$pickup_locker_name."%'";

			$pickup_lock =  DB::select($sql_pickup_lock);
			$lat1 = $pickup_lock[0]->latitude;
			$lon1 = $pickup_lock[0]->longitude;

			$sql_recipient_lock = "Select latitude, longitude from locker_locations
			where name like '%".$recipient_locker_name."%'";

			$recipient_lock =  DB::select($sql_recipient_lock);
			$lat2 = $recipient_lock[0]->latitude;
			$lon2 = $recipient_lock[0]->longitude;


			// locker to rumah
		} elseif ($pickup_address == "" && $recipient_locker_name == "") {
			if ($pickup_locker_name == "" ){
				$notif->setDataError();
				return response()->json($notif->build());
			}

			$sql_pickup_lock = "Select latitude, longitude from locker_locations
			where name like '%".$pickup_locker_name."%'";

			$pickup_lock =  DB::select($sql_pickup_lock);
			$lat1 = $pickup_lock[0]->latitude;
			$lon1 = $pickup_lock[0]->longitude;

			$lat2 = $recipient_address_lat;
			$lon2 = $recipient_address_long;


			// rumah to locker
		} elseif ($pickup_locker_name == "" && $recipient_address == "") {

			if ($recipient_locker_name == "" || $pickup_address_lat == "" || $pickup_address_long == "" ){
				$notif->setDataError();
				return response()->json($notif->build());
			}


			$lat1 = $pickup_address_lat;
			$lon1 = $pickup_address_long;

			$sql_recipient_lock = "Select latitude, longitude from locker_locations
			where name like '%".$recipient_locker_name."%'";

			$recipient_lock =  DB::select($sql_recipient_lock);
			$lat2 = $recipient_lock[0]->latitude;
			$lon2 = $recipient_lock[0]->longitude;
		}
		// echo $pickup_locker_name;
		// $theta = $lon1 - $lon2;
		// $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
		// $dist = acos($dist);
		// $dist = rad2deg($dist);
		// $miles = $dist * 60 * 1.1515;
		//
		// $pickdistance = $miles * 1.609344;
		//
		// $unit = "K";
		//
		// if ($pickdistance < 4000) {
		// 	$total_amount = 10000;
		// } else {
		// 	$distdiff = $pickdistance - 4000;
		// 	$gross_amount = 10000+($distdiff*1000);
		// 	$total_amount = ceil($gross_amount/100)*100;
		// }

		$total_amount = $this->calculatePrice($lat1, $lon1, $lat2, $lon2, 'S');
		if (is_null($total_amount)) {
			$resp=['response' => ['code' => '400','message' =>"Failed to Get GeoCode"], 'data' => []];
			return response()->json($resp);
		}
		if ($total_amount==0) {
			$resp=['response' => ['code' => '400','message' =>"Apologize your address is out of our coverage"], 'data' => []];
			return response()->json($resp);
		}

		$ins = array(
			'phone' => $phone,
			'pickup_address' => $pickup_address,
			'pickup_address_detail' => $pickup_address_detail,
			'pickup_address_name' => $pickup_address_name,
			'pickup_address_phone' => $pickup_address_phone,
			'pickup_address_lat' => $pickup_address_lat,
			'pickup_address_long' => $pickup_address_long,
			'pickup_locker_name' => $pickup_locker_name,
			'pickup_locker_size' => $pickup_locker_size,
			'pickup_date' => $pickup_date,
			'pickup_time' => $pickup_time,
			'item_description' => $item_description,
			'recipient_name' => $recipient_name,
			'recipient_phone' => $recipient_phone,
			'recipient_address' => $recipient_address,
			'recipient_address_detail' => $recipient_address_detail,
			'recipient_address_lat' => $recipient_address_lat,
			'recipient_address_long' => $recipient_address_long,
			'recipient_locker_name'=>$recipient_locker_name,
			'recipient_locker_size'=>$recipient_locker_size,
			'recipient_email'=>$recipient_email,
			'amount'=>$total_amount,
			'item_photo_1' => $item_photo_1,
			'item_photo_2' => $item_photo_2,
			'item_photo_3' => $item_photo_3
		);
	}

	$resp=['response' => ['code' => '200','message' =>"pickup request calculation"], 'data' => [$ins]];


	return response()->json($resp);
}
public function Pickupsubmit(Request $req) {

	$notif = $this->_checkToken($req->json('token'));

	if( $req->input('phone') )
	{
		$phone = $req->json('phone');
		$pickup_address = $req->json('pickup_address');
		$pickup_address_detail = $req->json('pickup_address_detail');
		$pickup_address_name = $req->json('pickup_address_name');
		$pickup_address_phone = $req->json('pickup_address_phone');
		$pickup_address_lat = $req->json('pickup_address_lat');
		$pickup_address_long = $req->json('pickup_address_long');
		$pickup_locker_name = $req->json('pickup_locker_name');
		$pickup_locker_size = $req->json('pickup_locker_size');
		$pickup_date = $req->json('pickup_date');
		$pickup_time = $req->json('pickup_time');
		$item_description = $req->json('item_description');
		$recipient_name = $req->json('recipient_name');
		$recipient_phone = $req->json('recipient_phone');
		$recipient_address = $req->json('recipient_address');
		$recipient_address_detail = $req->json('recipient_address_detail');
		$recipient_address_lat = $req->json('recipient_address_lat');
		$recipient_address_long = $req->json('recipient_address_long');
		$recipient_locker_name=$req->json('recipient_locker_name');
		$recipient_locker_size=$req->json('recipient_locker_size');
		$item_photo_1 = $req->json('item_photo_1');
		$item_photo_2 = $req->json('item_photo_2');
		$item_photo_3 = $req->json('item_photo_3');
		$recipient_email=$req->json('recipient_email');

	} else {
		$notif->setDataError();
		return response()->json($notif->build());
	}

	if($notif->isOK()) {

		//cek condition
		// locker to locker
		if ($pickup_address == "" && $recipient_address == "") {

			$invoice_id = "PLL".date('y').date('m').date('d').date('H').date('i').rand(0,999);
			$sql_pickup_lock = "Select latitude, longitude from locker_locations
			where name like '%".$pickup_locker_name."%'";

			$pickup_lock =  DB::select($sql_pickup_lock);
			$lat1 = $pickup_lock[0]->latitude;
			$lon1 = $pickup_lock[0]->longitude;

			$sql_recipient_lock = "Select latitude, longitude from locker_locations
			where name like '%".$recipient_locker_name."%'";

			$recipient_lock =  DB::select($sql_recipient_lock);
			$lat2 = $recipient_lock[0]->latitude;
			$lon2 = $recipient_lock[0]->longitude;

			$username = 'OPERATOR4API';
			$password = "p0pb0x4p10p3r"; // production pwd : popbox4514, development pwd : popbox123

			//$urlogin = env('API_URL')."/ebox/api/v1/user/login";
			$urlogin = "http://eboxapi.clientname.id:8080/ebox/api/v1/user/login";
			$varlogin = [
				'loginName' => $username,
				'password' => $password
			];

			$logres=$this->post_data($urlogin,json_encode ( $varlogin ), null);
			$api_token=$logres['token'];

			//$urlupload = env('API_URL')."/ebox/api/v1/express/staffImportCustomerStoreExpress";
			$urlupload = "http://eboxapi.clientname.id:8080/ebox/api/v1/express/staffImportCustomerStoreExpress";
			$this->headers[]="userToken:".$api_token;

			$data								= array();
			//	$data['orderNo']					= $invoice_id;
			$data['logisticsCompany']			= ['id' => '161e5ed1140f11e5bdbd0242ac110001'];
			$data['takeUserPhoneNumber']		= $recipient_phone;
			$data['customerStoreNumber']		= $invoice_id;
			//	$data['storeUserPhoneNumber']		= $phone;
			$data['chargeType']					= 'NOT_CHARGE';
			//	$data['designationSize']			= $recipient_locker_size;
			//	$data['expressNumber']				= $invoice_id;
			$data['recipientName']				= $recipient_name;
			$data['recipientUserPhoneNumber']	= $recipient_phone;
			//	$data['takeUserEmail']				= $recipient_email;
			$data['endAddress']					= $recipient_locker_name;

			$varupload = json_encode($data);

			// post api to pakpobox
			$upres=$this->post_data($urlupload, $varupload, $this->headers);

			// insert partner response to database
			DB::table('companies_response')
			->insert([
				'api_url' => $urlupload,
				'api_send_data' => $varupload,
				'api_response'  => json_encode($upres),
				'response_date'     => date("Y-m-d H:i:s")
			]);

			// locker to rumah
		} elseif ($pickup_address == "" && $recipient_locker_name == "") {
			$invoice_id = "PLA".date('y').date('m').date('d').date('H').date('i').rand(0,999);
			$sql_pickup_lock = "Select latitude, longitude from locker_locations
			where name like '%".$pickup_locker_name."%'";

			$pickup_lock =  DB::select($sql_pickup_lock);
			$lat1 = $pickup_lock[0]->latitude;
			$lon1 = $pickup_lock[0]->longitude;

			$lat2 = $recipient_address_lat;
			$lon2 = $recipient_address_long;

			$username = 'OPERATOR4API';
			$password = "p0pb0x4p10p3r"; // production pwd : popbox4514 development pwd : popbox123

			//$urlogin = env('API_URL')."/ebox/api/v1/user/login";
			$urlogin = "http://eboxapi.clientname.id:8080/ebox/api/v1/user/login";
			$varlogin = [
				'loginName' => $username,
				'password' => $password
			];

			$logres=$this->post_data($urlogin,json_encode ( $varlogin ), null);
			$api_token=$logres['token'];

			//$urlupload = env('API_URL')."/ebox/api/v1/express/staffImportCustomerStoreExpress";
			$urlupload =  "http://eboxapi.clientname.id:8080/ebox/api/v1/express/staffImportCustomerStoreExpress";
			$this->headers[]="userToken:".$api_token;

			$data								= array();
			//	$data['orderNo']					= $invoice_id;
			$data['logisticsCompany']			= ['id' => '161e5ed1140f11e5bdbd0242ac110001'];
			$data['takeUserPhoneNumber']		= $recipient_phone;
			$data['customerStoreNumber']		= $invoice_id;
			//	$data['storeUserPhoneNumber']		= $phone;
			$data['chargeType']					= 'NOT_CHARGE';
			//	$data['designationSize']			= $pickup_locker_size;
			//	$data['expressNumber']				= $invoice_id;
			$data['recipientName']				= $recipient_name;
			$data['recipientUserPhoneNumber']	= $recipient_phone;
			//	$data['takeUserEmail']				= $recipient_email;
			$data['endAddress']					= $recipient_address;

			$varupload = json_encode($data);

			// post api to pakpobox
			$upres=$this->post_data($urlupload, $varupload, $this->headers);

			// insert partner response to database
			DB::table('companies_response')
			->insert([
				'api_url' => $urlupload,
				'api_send_data' => $varupload,
				'api_response'  => json_encode($upres),
				'response_date'     => date("Y-m-d H:i:s")
			]);



			// rumah to locker
		} elseif ($pickup_locker_name == "" && $recipient_address == "") {
			$lat1 = $pickup_address_lat;
			$lon1 = $pickup_address_long;

			$sql_recipient_lock = "Select latitude, longitude from locker_locations
			where name like '%".$recipient_locker_name."%'";

			$recipient_lock =  DB::select($sql_recipient_lock);
			$lat2 = $recipient_lock[0]->latitude;
			$lon2 = $recipient_lock[0]->longitude;

			$invoice_id = "PAL".date('y').date('m').date('d').date('H').date('i').rand(0,999);

			$username = 'LOGISTIC4API';
			$password = "p0pb0x4p1l0g";

			//$urlogin = env('API_URL')."/ebox/api/v1/user/login";
			$urlogin = "http://eboxapi.clientname.id:8080/ebox/api/v1/user/login";
			$varlogin = [
				'loginName' => $username,
				'password' => $password
			];

			$logres=$this->post_data($urlogin,json_encode ( $varlogin ), null);
			$api_token=$logres['token'];

			//$urlupload = env('API_URL')."/ebox/api/v1/express/import";
			$urlupload = "http://eboxapi.clientname.id:8080/ebox/api/v1/express/import";
			$this->headers[]="userToken:".$api_token;

			$varupload = [
				'expressNumber' => $invoice_id,
				'takeUserPhoneNumber' => $recipient_phone
			];
			$varupload = "[".json_encode($varupload)."]";

			// post api to pakpobox
			$upres=$this->post_data($urlupload, $varupload, $this->headers);

			// insert partner response to database
			DB::table('companies_response')
			->insert([
				'api_url' => $urlupload,
				'api_send_data' => $varupload,
				'api_response'  => json_encode($upres),
				'response_date'     => date("Y-m-d H:i:s")
			]);

		}

		// $theta = $lon1 - $lon2;
		// $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
		// $dist = acos($dist);
		// $dist = rad2deg($dist);
		// $miles = $dist * 60 * 1.1515;
		//
		// $pickdistance = $miles * 1.609344;
		//
		// $unit = "K";
		//
		// if ($pickdistance < 4000) {
		// 	$total_amount = 10000;
		// } else {
		// 	$distdiff = $pickdistance - 4000;
		// 	$gross_amount = 10000+($distdiff*1000);
		// 	$total_amount = ceil($gross_amount/100)*100;
		// }
		$total_amount = $this->calculatePrice($lat1, $lon1, $lat2, $lon2, $pickup_locker_size);
		if (is_null($total_amount)) {
			$resp=['response' => ['code' => '400','message' =>"Failed to Get GeoCode"], 'data' => []];
			return response()->json($resp);
		}
		if ($total_amount==0) {
			$resp=['response' => ['code' => '400','message' =>"Apologize your address is out of our coverage"], 'data' => []];
			return response()->json($resp);
		}


		// upload parcel photos
		$output_file_1 = env('IMG_PATH')."member/".$invoice_id."_1.jpg";
		$output_file_name_1 = $invoice_id."_1.jpg";
		$ifp = fopen($output_file_1, "wb");
		fwrite($ifp, base64_decode($item_photo_1));
		fclose($ifp);

		if ($item_photo_2 != "") {
			$output_file_2 = env('IMG_PATH')."member/".$invoice_id."_2.jpg";
			$output_file_name_2 = $invoice_id."_2.jpg";
			$ifp = fopen($output_file_2, "wb");
			fwrite($ifp, base64_decode($item_photo_2));
			fclose($ifp);
		} else {
			$output_file_name_2 = "";
		}

		if ($item_photo_3 != "") {
			$output_file_3 = env('IMG_PATH')."member/".$invoice_id."_3.jpg";
			$output_file_name_3 = $invoice_id."_3.jpg";
			$ifp = fopen($output_file_3, "wb");
			fwrite($ifp, base64_decode($item_photo_3));
			fclose($ifp);
		} else {
			$output_file_name_3 = "";
		}

		$output_file_name = $output_file_name_1 .",".$output_file_name_2 .",".$output_file_name_3;

		$ins = array(
			'phone' => $phone,
			'invoice_id' =>$invoice_id,
			'pickup_address' => $pickup_address,
			'pickup_address_detail' => $pickup_address_detail,
			'pickup_address_name' => $pickup_address_name,
			'pickup_address_phone' => $pickup_address_phone,
			'pickup_address_lat' => $pickup_address_lat,
			'pickup_address_long' => $pickup_address_long,
			'pickup_locker_name' => $pickup_locker_name,
			'pickup_locker_size' => $pickup_locker_size,
			'pickup_date' => $pickup_date,
			'pickup_time' => $pickup_time,
			'item_description' => $item_description,
			'recipient_name' => $recipient_name,
			'recipient_phone' => $recipient_phone,
			'recipient_address' => $recipient_address,
			'recipient_address_detail' => $recipient_address_detail,
			'recipient_address_lat' => $recipient_address_lat,
			'recipient_address_long' => $recipient_address_long,
			'recipient_locker_name'=>$recipient_locker_name,
			'recipient_locker_size'=>$recipient_locker_size,
			'recipient_email'=>$recipient_email,
			'amount'=>$total_amount,
			'item_photo' => $output_file_name,
			'pickup_order_date' => date("Y-m-d H:i:s")
		);

		$res = DB::table('tb_member_pickup')->insertGetId($ins);

		// deduct balance
		$sqlbal = "Select current_balance from tb_member_balance where phone='".$phone."' order by last_update desc limit 0,1";
		$bl = DB::select($sqlbal);
		$blc = count($bl);

		if ($blc != 0) {
			$prev_balance = $bl[0]->current_balance;
			$current_balance = $prev_balance-$total_amount;
			DB::table('tb_member_balance')
				->insert(['phone' => $phone,
				'deduction_amount' => $total_amount,
				'prev_balance'  => $prev_balance,
				'current_balance'     => $current_balance,
				'invoice_id' => $invoice_id,
				'last_update' => date('Y-m-d H:i:s')
			]);

		} else {
			DB::table('tb_member_balance')
			->insert(['phone' => $phone,
			'deduction_amount' => $total_amount,
			'prev_balance'  => 0,
			'current_balance'  => -$total_amount,
			'invoice_id' => $invoice_id,
			'last_update' => date('Y-m-d H:i:s')
		]);
	}

	//get member gcm
	$sqlgcm = "select member_gcm_token from tb_member where phone='".$phone."'";
	$rsgcm = DB::select($sqlgcm);
	$gcm = $rsgcm[0]->member_gcm_token;



	$urlupload = "https://gcm-http.googleapis.com/gcm/send";

	$this->headers[]="Authorization:key=".env('GCM_KEY');

	$varupload = [
		'data' => ['service'=>'pickup request', 'detail'=> ['invoice_id'=> $invoice_id, 'balance_deduct' => $total_amount], 'title'=>'Anda telah melakukan pemesanan pick up request'],
		'to' => $gcm
	];
	$varupload = json_encode($varupload);

	// post notification to google
	$upres=$this->post_data($urlupload, $varupload, $this->headers);

	// insert partner response to database
	DB::table('companies_response')
	->insert([
		'api_url' => $urlupload,
		'api_send_data' => $varupload,
		'api_response'  => json_encode($upres),
		'response_date'     => date("Y-m-d H:i:s")
	]);


	// send sms to user
	$message = "Pilih menu Mengirim Barang masukkan kode ".$invoice_id." di loker ".$pickup_locker_name.". Rincian info: https://popsend.clientname.id/ord/".$invoice_id;
	;

	//   send sms using jatis
	$sms = json_encode(['to' => $phone,
	'message' => $message,
	'token' => '0weWRasJL234wdf1URfwWxxXse304'
	]);
	$urlsms = "http://smsdev.clientname.id/sms/send";

	// send sms using twillio
	/*    $phone = substr($phone, 1);
	$phone = "+62".$phone;

	$sms = json_encode(['to' => $phone,
	'message' => $message,
	'token' => '2349oJhHJ20394j2LKJO034823423'
	]);
	$urlsms = "http://smsdev.clientname.id/sms/send/tw";
	*/
	$resp=$this->post_data($urlsms, $sms);

	// $resp = json_decode($this->curl->post($urlsms, $sms), true);

	// insert partner response to database
	DB::table('companies_response')
	->insert([
		'api_url' => $urlsms,
		'api_send_data' => $sms,
		'api_response'  => json_encode($resp),
		'response_date'     => date("Y-m-d H:i:s")
	]);
	}
	// Send email
	#get locker data
	$sql_pickup_lock = "Select name, address, address_2, operational_hours from locker_locations
	where name like '%".$pickup_locker_name."%'";
	$recipient_lock =  DB::select($sql_pickup_lock);
	$lockerData = $recipient_lock[0];

	#get member detail
	$userData = DB::table('tb_member')->where('phone','=',$phone)->first();
	$email =  $userData->email;
	$name = $userData->member_name;
	if (!empty($email)) {
		$data['detail'] = $ins;
		$data['locker_detail'] = $lockerData;
		if (!file_exists(public_path().'/pdf/invoice/'.$invoice_id.'.pdf')) {
			PDF::loadView('pdf.invoice',$data)->save(public_path().'/pdf/invoice/'.$invoice_id.'.pdf');
		}
		Mail::send('email.invoice', ['detail'=>$ins,'locker_detail'=>$lockerData,'member_name'=>$name], function ($m) use($invoice_id,$email,$name){
			$m->from('admin@clientname.id', 'PopSend Invoice');
			$m->to($email, $name)->subject('PopSend Invoice '.$invoice_id);
			$m->attach(public_path().'/pdf/invoice/'.$invoice_id.'.pdf');
		});
	}

	$resp=['response' => ['code' => '200','message' =>"pickup request submit success"], 'data' => [['total_amount'=> $total_amount,'invoice_id'=>$invoice_id]]];


	return response()->json($resp);
}


public function Pickuphistory(Request $req) {

	$notif	= $this->_checkToken($req->json('token'));


	if( $req->input('phone') && $req->input('page') ) {
		$phone = $req->json('phone');
		$page = $req->json('page') - 1;
		$offset = 10 * $page ;
	} else {
		$notif->setDataError();
		return response()->json($notif->build());
	}

	if($notif->isOK()) {

		$sql = "select a.invoice_id, a.pickup_address, a.pickup_locker_name, a.pickup_locker_size, a.item_description,
		a.recipient_name, a.recipient_phone, a.recipient_address, a.recipient_address_detail, a.recipient_locker_name,
		a.recipient_locker_size, a.recipient_email,
		case
		when c.receiver_name is not null then 'COMPLETED'
		when b.status like '%in_store%' then 'IN_STORE'
		when b.status like '%taken%' then 'COURIER_TAKEN'
		when b.status is null then 'NEED_STORE'
		end as status,
		a.amount as total_amount,
		a.pickup_order_date as order_date
		from tb_member_pickup a
		left outer join return_statuses c
		on a.invoice_id=c.number
		left outer join locker_activities_pickup b
		on a.invoice_id=b.tracking_no where a.phone='".$phone."'
		order by a.pickup_order_date desc limit ".$offset.",10";

		$resp = DB::select($sql);
		$blc = count($resp);
		$total = ceil($blc/10);

		$notif->setOK($total,$resp);

	}

	return response()->json($notif->build());
}

public function PickupDetail(Request $req) {
	$notif	= $this->_checkToken($req->json('token'));


	if( $req->input('phone') && $req->input('invoice_id') ) {
		$phone = $req->json('phone');
		$invoice_id = $req->json('invoice_id');
	} else {
		$notif->setDataError();
		return response()->json($notif->build());
	}

	if($notif->isOK()) {

		$sql = "select a.invoice_id, a.pickup_address, a.pickup_locker_name, a.pickup_locker_size, a.item_description,
		a.recipient_name, a.recipient_phone, a.recipient_address_detail, a.recipient_address, a.recipient_locker_name,
		a.recipient_locker_size, a.recipient_email,
		case
		when c.receiver_name is not null then 'COMPLETED'
		when b.status like '%in_store%' then 'IN_STORE'
		when b.status like '%taken%' then 'COURIER_TAKEN'
		when b.status is null then 'NEED_STORE'
		end as status,
		case
		when c.receiver_name is not null then CONCAT('IN_STORE : ', b.storetime , ', COURIER_TAKEN : ',b.taketime, ', RECEIVED_DATE : ', c.updated_at , ', RECEIVED_BY : ',c.receiver_name)
		when b.status like '%in_store%' then CONCAT('IN_STORE : ', b.storetime)

		when b.status like '%taken%' then CONCAT('IN_STORE : ', b.storetime , ', COURIER_TAKEN : ',b.taketime)
		when b.status is null then 'NEED_STORE'
		End as status_history,
		a.amount as total_amount,
		a.pickup_order_date as order_date
		from tb_member_pickup a
		left outer join locker_activities_pickup b
		on a.invoice_id=b.tracking_no
		left outer join return_statuses c
		on a.invoice_id=c.number where a.invoice_id='".$invoice_id."'";

		$resp = DB::select($sql);
		$blc = count($resp);
		$total = 1;

		$notif->setOK($total,$resp);

	}

	return response()->json($notif->build());

}

public function PickupInvoiceDetail(Request $req) {
	$notif  = $this->_checkToken($req->json('token'));


	if( $req->input('invoice_id') ) {
		$invoice_id = $req->json('invoice_id');
	} else {
		$notif->setDataError();
		return response()->json($notif->build());
	}

	if($notif->isOK()) {

		$sql = "select a.invoice_id, a.pickup_address, a.pickup_locker_name, a.pickup_locker_size, a.item_description,
		a.recipient_name, a.recipient_phone, a.recipient_address_detail, a.recipient_address, a.recipient_locker_name,
		a.recipient_locker_size, a.recipient_email,
		case
		when b.taketime='0000-00-00 00:00:00' and b.overduetime < now() then 'OVERDUE'
		when b.status like '%in_store%' then 'IN_STORE'
		when b.status like '%taken%' then 'CUSTOMER_TAKEN'
		when b.status is null then 'IN_PROCESS'
		End as status,
		a.amount as total_amount,
		a.pickup_order_date as order_date
		from tb_member_pickup a
		left outer join locker_activities b
		on a.invoice_id=b.barcode where a.invoice_id='".$invoice_id."'";

		$resp = DB::select($sql);
		$blc = count($resp);
		$total = 1;

		$notif->setOK($total,$resp);

	}

	return response()->json($notif->build());

}

/**
 * Calculate Price
 * @param  [type] $latOrigin  [description]
 * @param  [type] $longOrigin [description]
 * @param  [type] $latDest    [description]
 * @param  [type] $longDest   [description]
 * @param  [type] $parcelType [description]
 * @return [type]             [description]
 */
private function calculatePrice($latOrigin, $longOrigin,$latDest, $longDest, $parcelType='S')
{
	// set default
	$amount = null;
	// get city name for origin and destination
	$cityOrigin = $this->googleGeoCode($latOrigin, $longOrigin);
	$cityDest = $this->googleGeoCode($latDest, $longDest);
	// if google geocode failed
	if (empty($cityOrigin) || empty($cityDest)) {
		return $amount;
	}

	$jadetabek = ['Kota Jakarta Pusat','Kota Jakarta Utara','Kota Jakarta Selatan','Kota Jakarta Timur','Kota Jakarta Barat','Kota Depok','Kota Tangerang','Kota Bekasi','Bekasi','Tangerang','Kota Tangerang Selatan'];
	$parcel = array(
		'S'=>2.72,
		'M'=>5.44,
		'L'=>8.976
	);
	$parcelType = strtoupper($parcelType);
	// Kalau dua duanya jadetabek
	$countOrigin = count(array_intersect($cityOrigin, $jadetabek));
	$countDest = count(array_intersect($cityDest, $jadetabek));

	if ($countOrigin > 0 && $countDest > 0 ) {
		$tarifDB = DB::table('tb_popsend_tarif')->whereIn('origin',$cityOrigin)->whereIn('destination',$cityDest)->where('parcel_size','=',$parcelType)->first();
		if ($tarifDB) {
			$amount = $tarifDB->price;
		} else $amount = 0;
	} else {
		$tarifDB = DB::table('tb_popsend_tarif')->whereIn('origin',$cityOrigin)->whereIn('destination',$cityDest)->first();
		if ($tarifDB) {
			$pricePerKg = $tarifDB->price;
			if (!in_array($parcelType, $parcel)) {
				$parcelType = 'S';
			}
			$amount = $parcel[$parcelType] * $pricePerKg;
		} else $amount = 0;
	}
	return $amount;
}

/**
 * Google GeoCode to get Detail from lat and long
 * @param  [type] $lat  [description]
 * @param  [type] $long [description]
 * @return [type]       [description]
 */
private function googleGeoCode($lat, $long){
	// set default value
	$cityName = array();
	// set URL Google GeoCode with lat and long
	$url = "https://maps.googleapis.com/maps/api/geocode/json?latlng=$lat,$long&key=AIzaSyC73T7dCEeJBcF06rEsa8Ea_WEsjl2RyGY";

	$msg = ' Request : '.$url;

	$dataJson = file_get_contents($url);
	$dataJson = json_decode($dataJson);
	// if ok
	if ($dataJson->status=='OK') {
		foreach ($dataJson->results as $key => $value) {
			$address_components = $value->address_components; #get address component pertama
			// Ambil hanya administrative area level 2
			foreach ($address_components as $key => $value) {
				if (in_array('administrative_area_level_2', $value->types)) {
					if (!in_array($value->long_name,$cityName)) {
						$cityName[] = $value->long_name;
					}
				}
			}
		}
	}
	$msg .= ' Response : '.json_encode($cityName);

	$this->logData($msg);

	return $cityName;
}

private function logData($msg = ''){
	static $id;
    if (empty($id)) $id = uniqid();
    //if (!__IS_DEV__) return;
    $msg = date('Ymd.His')." $id $msg\n";
    $f = fopen(storage_path().'/logs/geocode'.date('.Y.m.d.').'log','a');
    fwrite($f,date('H:i:s')." $msg");
    fclose($f);
}

private function _checkToken($token) {
	$notif	= new \App\Http\Helpers\NotificationHelper();

	//cek database company token
	$sqlck = "SELECT count(*) as cektoken FROM api_company_access_tokens where access_token='".$token."'";
	$ck = DB::select($sqlck);
	$cek = $ck[0]->cektoken;

	if($token == '' || $cek == 0 ) {
		$notif->setUnauthorized();
	}

	return $notif;
}

}
