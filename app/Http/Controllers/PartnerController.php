<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\DB;

use App\Http\Helpers\NotificationHelper;
use App\Http\Helpers\WebCurl;
use App\Http\Helpers\CompanyLogResponse;

//models
use App\PartnerOrders;
use App\PartnerOrderStatus;
use App\CompanyToken;
use App\CompanyResponse;

class PartnerController extends Controller
{
    
	public function saveOrders(Request $req) {
		$notif = $this->_validateOrders($req);
		
		if($notif->isOK()) {
			$orderNumber = $req->json('order_number');
			
			$orders = new PartnerOrders();
			$orders->token = $req->json('token');
			$orders->order_number = $orderNumber;
			$orders->weight = $req->json('weight');
			$orders->phone_number = $req->json('phone_number');
			$orders->email = $req->json('email', '');
			$orders->locker_location = $req->json('locker_location');
			$orders->description = $req->json('description', '');
			$orders->max_drop_time = $req->json('max_drop_time');
			$orders->save();
			
			if($orders->id != null && $orders->id != '') {
				$this->_lockerNeedStore($orders->id);
				$notif->setOK(1, $orders->id);
			} else {
				$notif->setInternalServerError();
			}
		}
		
		return response()->json($notif->build());
	}
	
	public function updateStatus(Request $req) {
		$notif = $this->_validateStatus($req);
		if($notif->isOK()) {
			$orders = PartnerOrders::where('order_number', $req->json('order_number'))
						->where('token', $req->json('token'))
						->get();
			
			if($orders->count() != 1) {
				$notif->addErrorMessage('order_number', 'Invalid Order Number');
			} else {
				$orderStatus = $req->json('status');
				$orderId = $orders[0]->id;
				
				$status = new PartnerOrderStatus();
				$status->order_id = $orderId;
				$status->status = $orderStatus;
				$status->save();
				
				$result = true;
				if(strtolower($orderStatus) == 'delivery') {
					$result = $this->_lockerDelivery($orderId);
				} else if(strtolower($orderStatus) == 'no-show') {
//					$result = $this->_lockerCanceled($orderId);
				} else if(strtolower($orderStatus) == 'picked-up') {
					$result = $this->_sendSms($orderId);
				}
				
				if($result == true)
					$notif->setOK(1, $status->id);
				else
					$notif->setInternalServerError ();
			}
		}
		
		return response()->json($notif->build());
	}
	



	private function _validateOrders(Request $req) {
		$notif = new NotificationHelper();
		
		if($req->json('order_number') == '') {
			$notif->addErrorMessage('order_number', 'Order ID required');
		}
		if($req->json('locker_location') == '') {
			$notif->addErrorMessage('locker_location', 'Locker Name required');
		}
		if(!is_numeric($req->json('weight')) || $req->json('weight') < 1) {
			$notif->addErrorMessage('weight', 'Item\'s weight required more than zero');
		}
		if($req->json('phone_number') == '') {
			$notif->addErrorMessage('phone_number', 'Customer\'s phone number required');
		}
		
		return $notif;
	}
	
	private function _validateStatus(Request $req) {
		$notif = new NotificationHelper();
		
		if($req->json('order_number') == '') {
			$notif->addErrorMessage('order_number', 'Order Number required');
		}
		if($req->json('status') == '') {
			$notif->addErrorMessage('status', 'Status required');
		}
		
		return $notif;
	}
	
	private function _courierCompanyID($orderNumber) {
		$ids = [
			'TAP' => '40288083566d7b7f01568bcf5e1b703f' /*TapToPick*/
		];
				
		$prefix = substr($orderNumber, 0, 3);
		
		return isset($ids[$prefix]) ? $ids[$prefix] : '161e5ed1140f11e5bdbd0242ac110001';
	}
	
	private function _lockerSize($weight) {
		if($weight >= 4.0)
			return 'L';
		else
			return 'M';
	}
	
	private function _lockerLogin($userName, $password) {
		$urlogin = "http://eboxapi.clientname.id:8080/ebox/api/v1/user/login";
		$varlogin = ['loginName' => $userName, 'password' => $password];

		$curl = new WebCurl(['Content-Type: application/json']);			
		$response = json_decode($curl->post($urlogin, json_encode($varlogin)), true);
		
		return isset($response['token']) ? $response['token'] : '';
	}
	
	private function _sendSms($orderId) {
		$result = false;
		
		$order = PartnerOrders::where('id', $orderId)->first();
		if($order != null) {
			$msg = 'Pickr telah mengambil pakaian Anda dg order number '.$order->order_number;
			$msg .= ' di '.$order->locker_location.'. Pakaian bersih dpt diambil kembali di loker dlm 72 jam.';
			
			$curl = new WebCurl(['Content-Type: application/json']);
			$url = 'http://smsdev.clientname.id/sms/send';
			$params = json_encode(['to'=>$order->phone_number, 'message'=>$msg, 'token'=>'0weWRasJL234wdf1URfwWxxXse304']);
			$response = $curl->post($url, $params);
			
			(new CompanyLogResponse($url, $params, $response))->save();
			
			$result = true;
		}
		
		return $result;
	}
	
	private function _lockerNeedStore($orderId) {
		$result = false;
		
		$order = PartnerOrders::where('id', $orderId)->first();
		
		if($order != null) {
			$orderNumber = $order->order_number;
			$companyId = $this->_courierCompanyID($orderNumber);
			
//			if($companyId != '') {
				$username = 'OPERATOR4API';
				$password = "p0pb0x4p10p3r";
				$api_token = $this->_lockerLogin($username, $password);

				if($api_token != '') {
					$curl = new WebCurl(['Content-Type: application/json', 'userToken:'.$api_token]);

					$urlupload = "http://eboxapi.clientname.id:8080/ebox/api/v1/express/staffImportCustomerStoreExpress";

					$data								= array();
					$data['logisticsCompany']			= ['id' => $companyId];
					$data['takeUserPhoneNumber']		= $order->phone_number;
					$data['customerStoreNumber']		= $orderNumber;
					$data['chargeType']					= 'NOT_CHARGE';
					$data['designationSize']			= $this->_lockerSize($order->weight);
					$data['recipientName']				= 'Customer';//$recipient_name;
					$data['recipientUserPhoneNumber']	= $order->phone_number;
					if($order->email != '') {
						$data['takeUserEmail']			= $order->email;
					}
					$data['endAddress']					= $order->locker_location;

					$varupload = json_encode($data);
					
					// post api to pakpobox        			
					$upres = $curl->post($urlupload, $varupload);

					// insert partner response to database
					(new CompanyLogResponse($urlupload, $varupload, $upres))->save();
					
					$result = true;
				}
//			}
		}
		
		return $result;
	}
	
	private function _lockerDelivery($orderId) {
		$result = false;
		
		$order = PartnerOrders::where('id', $orderId)->first();
		if($order != null) {
			$username = 'TAPTOPICK4API';//'LOGISTIC4API';
			$password = '718104API';//'p0pb0x4p1l0g';
			$api_token = $this->_lockerLogin($username, $password);

			if($api_token != '') {
				$curl = new WebCurl(['Content-Type: application/json', 'userToken: '.$api_token]);

				$urlupload = "http://eboxapi.clientname.id:8080/ebox/api/v1/express/import";
				$varupload = json_encode([['expressNumber' => $order->order_number,  'takeUserPhoneNumber' => $order->phone_number]]);

				// post api to pakpobox       
				$upres = $curl->post($urlupload, $varupload);

				// insert partner response to database
				(new CompanyLogResponse($urlupload, $varupload, $upres))->save();
				
				$result = true;
			}
		}
		
		return $result;
	}
	
	public function _lockerCanceled($orderId) {
		$result = false;
		
		$order = DB::table('partner_orders')
					->join('locker_activities_pickup', 'partner_orders.order_number', '=', 'locker_activities_pickup.tracking_no')
					->select('locker_activities_pickup.id AS box_id')
					->where('partner_orders.id', $orderId)
					->first();
		
		if($order != null) {
			$username = 'TAPTOPICK4API';//'LOGISTIC4API';
			$password = '718104API';//'p0pb0x4p1l0g';
			$api_token = $this->_lockerLogin($username, $password);

			if($api_token != '') {
				$curl = new WebCurl(['Content-Type: application/json', 'userToken: '.$api_token]);

				$urlupload = "http://eboxapi.clientname.id:8080/ebox/api/v1/task/express/resetExpress";
				$varupload = json_encode(['id' => $order->box_id,  'overdueTime' => (strtotime('2016-01-01')*1000)]);

				// post api to pakpobox       
				$upres = $curl->post($urlupload, $varupload);

				// insert partner response to database
				(new CompanyLogResponse($urlupload, $varupload, $upres))->save();
				
				$result = true;
			}
		}
		
		return $result;
	}
	
	public function updateDelivery (Request $req) {
		$notif = $this->_checkToken($req->json('token'));

		if($req->input('order_number') && $req->input('status') ) {
				$token = $req->json('token');
				$order_number = $req->json('order_number');
				$status = $req->json('status');
		} else {
			   $notif->setDataError();
			   return response()->json($notif->build());
		}

		if($notif->isOK()) {
			//get partner name
			$sqlp = "SELECT b.company_name from api_company_access_tokens a, companies b where a.company_id=b.id and a.access_token='".$token."'";
			$part = DB::select($sqlp);
			$partner_name = $part[0]->company_name;
			
			echo $partner_name;

			DB::table('partner_delivery_status')
                ->insert([
                		'partner_name' => $partner_name,
                        'order_number' => $order_number,
                        'status'  => $status,
                        'update_date' => date("Y-m-d H:i:s")
                        ]);
		}	

		$res=['response' => ['code' => '200','message' =>"success submit data"]];
       		 return response()->json($res);

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
