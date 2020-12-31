<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\DB;
use App\Http\Helpers\finclude\Core\DokuInitiate; 
use App\Http\Helpers\finclude\Core\DokuApi;
use App\Http\Helpers\finclude\Core\DokuLibrary; 
use App\Users;

class PaymentController extends Controller
{	
	
	public function __construct() {
		
	}	
	public function Paymentconfirm(Request $req) {
			
		$notif = $this->_checkToken($req->json('token'));

			if($req->input('invoice_number') && $req->input('phone') ) {
				$phone = $req->json('phone');
				$invoice_id = $req->json('invoice_number');
				$bank_transfer_dest = $req->json('bank_transfer_dest');
				$sender_bank_name = $req->json('sender_bank_name');
				$sender_bank_account_number = $req->json('sender_account_number');
				$transfer_amount = $req->json('amount');
				$payment_date = $req->json('payment_date');
			} else {
			   $notif->setDataError();
			   return response()->json($notif->build());
			}


			if($notif->isOK()) {
				//cek if data exist in database
				$transaction_count = Users::getTransactionByParams(array("invoice_id"=>$invoice_id));
				$transfer_count = Users::getTransfByParams(array("invoice_id"=>$invoice_id));
				if ($transaction_count==0){
					$notif->setDataError();
			   		return response()->json($notif->build());		
				}else if ($transfer_count>=1){
					$notif->setDataError();
			   		return response()->json($notif->build());		
				}
				
				$sqlck = "select count(invoice_id) as cekpay from tb_bank_transfer_payment where invoice_id = '".$invoice_id."'";
				$rest = DB::select($sqlck);
				$ck = $rest[0]->cekpay;

				if ($ck == 0) {
       				 DB::table('tb_bank_transfer_payment')
                    ->insert([
                            'phone' => $phone,
                            'invoice_id' => $invoice_id,
                            'bank_transfer_dest'  => $bank_transfer_dest,
                            'sender_bank_name' => $sender_bank_name,
                            'sender_bank_account_number' => $sender_bank_account_number,
                            'transfer_amount' => $transfer_amount,
                            'payment_date'     => $payment_date
                			]);
				} else {
					  DB::table('tb_bank_transfer_payment')
                    ->where('invoice_id', $invoice_id)
                    ->update(array(
                    	'phone' => $phone,
                     	'bank_transfer_dest'  => $bank_transfer_dest,
                     	'sender_bank_name' => $sender_bank_name,
                     	'sender_bank_account_number' => $sender_bank_account_number,
                        'transfer_amount' => $transfer_amount,
                        'payment_date'     => $payment_date
                    ));   
				}

       		 $res=['response' => ['code' => '200','message' =>"success send payment confirmation to admin"], 'data' => []];
       		 return response()->json($res);       
			}
		  
	}

	public function doku(){
		return view('doku/index');  
	}

	
	public function MobilePayment(Request $req){
		$ipRemoteFrom = self::_get_client_ip();		
		if (!in_array($ipRemoteFrom, config('config.ip_access'))){           
            die("Api not found");
        }  
		$notif = $this->_checkToken($req->json('token'));
		if (!$notif->isOK())	{
			$notif->setDataError();
			return response()->json($notif->build());
			die();
		}
		$phone = $req->json('res_data_mobile_phone');
		$uid_token = $req->json('uid_token');
		$users = Users::checkTokenUser($phone, $uid_token);			
		if (isset($users)){		
		 	self::_dokuConfirmation($req);
		}else{
			$response = Array("response"=> array("code"=>"400","message"=>"failed","description"=>"Users Not Found", "error_code"=>""),"data"=>Array());
			echo json_encode($response);
		}
	}

	public function paymentTransf(Request $req){
		$notif = $this->_checkToken($req->json('token'));		
		$phone = $req->json('phone',"");
		$uid_token = $req->json('uid_token','');
		$users = Users::checkTokenUser($phone, $uid_token);		
		
		if(!$notif->isOK()) {
			$notif->setDataError();
			return response()->json($notif->build());
		}
		if (empty($users)){
			$notif->setDataError();
			return response()->json($notif->build());	
		}
		
		if ($req->json('bankname') && $req->json('amount')){
			$bankname = $req->json('bankname');
			$amount = $req->json('amount');
			$invoice_id = $phone."BAL".time().rand(5, 15);	
			DB::table('tb_bank_transf_transaction')->insert([
            			"bank_name"=>$bankname,
            			"invoice_id" => $invoice_id,
            			"amount" => $amount
            	]);			
			DB::table('tb_transaction')->insert([
            			"type"=>"Bank-transfer",
            			"invoice_id" => $invoice_id,
            			"status" => "Order"
            	]);		
			$res=array('response' => array('code' => '200','message' =>"success create transaction bank transfer",'result' => array("invoice_id" => $invoice_id) ));
       		 return response()->json($res);
		}else{
			$notif->setDataError();
			return response()->json($notif->build());
		}
	}
	

	public function dokucc(Request $req){
		$notif = $this->_checkToken($req->json('token'));
		
		if($req->json('TRANSIDMERCHANT') && $req->json('AMOUNT') ) {
				$payment_time = $req->json('PAYMENTDATETIME');
				$currency = $req->json('PURCHASECURRENCY');
				$payment_channel = $req->json('PAYMENTCHANNEL');
				$amount = $req->json('AMOUNT');
				$mcn = $req->json('MCN');
				$resultmsg = $req->json('RESULTMSG');
				$transidmerchant = $req->json('TRANSIDMERCHANT');
				$bank =  $req->json('BANK');
				$statustype =  $req->json('STATUSTYPE');
				$approvalcode =  $req->json('APPROVALCODE');
				$threedesecurestatus =  $req->json('THREEDSECURESTATUS');
				$response_code =  $req->json('RESPONSECODE');
				$chname =  $req->json('CHNAME');
				$brand =  $req->json('BRAND');
				$sessionid =  $req->json('SESSIONID');
		} else {
			   $notif->setDataError();
			   return response()->json($notif->build());
		}

		if($notif->isOK()) {
			 DB::table('doku_cc_settlement')
                    ->insert([
                            'payment_time' => $payment_time,
                            'currency' => $currency ,
                            'payment_channel'  => $payment_channel,
                            'amount' => $amount,
                            'mcn' => $mcn,
                            'resultmsg' => $resultmsg,
                            'transidmerchant' => $transidmerchant,
                            'bank' => $bank,
                            'statustype' => $statustype,
                            'approvalcode' => $approvalcode,
                            'threedesecurestatus' => $threedesecurestatus,
                            'response_code'  => $response_code,
                            'chname'  => $chname,
                            'brand' => $brand,
                            'sessionid' => $sessionid,
                            'submit_time' => date("Y-m-d H:i:s")
                			]);
                    $type = "";
                    if ($chname=="04"){
                    		$tyle="Doku-Wallet";
                    }else if ($chname=="15"){
                    	$type = "Doku-credit_card";	
                    }
            	DB::table('tb_transaction')->insert([
            			"type"=>$type,
            			"id_invoice" => $transidmerchant,
            			"status" => $response_code 
            	]);
            $res=['response' => ['code' => '200','message' =>"success send cc payment data to database"], 'data' => []];
       		 return response()->json($res);
        
        }            
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

	private function _dokuConfirmation(Request $req){		
		if( !$req->input('res_data_mobile_phone')){
			 $notif->setDataError();
			 return response()->json($notif->build());
			 die();
		}		
		
		if (!function_exists('curl_init')) {
		  throw new Exception('CURL PHP extension missing!');
		}
		if (!function_exists('json_decode')) {
		  throw new Exception('JSON PHP extension missing!');
		}
		
		$data = Array(
			"token" => $req->json('res_token_id'),
			"deviceid" => $req->json("res_device_id"),
			"pairing_code" => $req->json('res_pairing_code'),
			"invoice_no" => $req->json('res_transaction_id'),
			"amount" => $req->json('res_amount'),
			"name" => $req->json('res_name'),
			"phone" => $req->json('res_data_mobile_phone'),
			"channel" => $req->json('res_payment_channel'),
			"email" =>$req->json('res_data_email'));

		$prossesResult = self::_dokuTransaction($data);		
		$result = $prossesResult["response"];
		$dataPayment = $prossesResult["data_payment"];
		$code = "400";
		$err_description = "";
		$err_code = "";
		if ($result->res_response_code=="0000"){			
			$code = "200";
		}else{			
			$err_description = $result->res_response_msg;
			$err_code = $result->res_response_code;
		}
		$response = Array("response"=> array("code"=>$code,"message"=>"failed","description"=>$err_description, "error_code"=>$err_code),"data"=>Array());
		echo json_encode($response);
	}

	private function _dokuTransaction($paramsP){

		$doku = new DokuInitiate();
		$dokuApi = new DokuApi();
		$doku::$sharedKey = "fh7Au8FwUL73";
		$doku::$mallId = "3443";			
		
		$params = array(
			'amount' => $paramsP['amount'],
			'invoice' => $paramsP['invoice_no'],
			'currency' => '360',
			'pairing_code' => $paramsP['pairing_code'],
			'token' => $paramsP['token'],
			'deviceid' => $paramsP['deviceid']
			);
		
		$words = DokuLibrary::doCreateWords($params);

		$basket[] = array(
			'name' => $paramsP['name'],
			'amount' => $paramsP['amount'],
			'quantity' => '1',
			'subtotal' => $paramsP['amount']
		);
		$customer = array(		
			'name' => $paramsP['name'],
			'data_phone' => $paramsP['phone'],
			'data_email' => $paramsP['email'],
			'data_address' => 'data address'
		);
		$dataPayment = array(
			'req_mall_id' => $doku::$mallId,
			'req_chain_merchant' => 'NA',
			'req_amount' => $paramsP['amount'],
			'req_words' => $words,
			'req_mobile_phone'=> $paramsP['phone'],
			'req_purchase_amount' =>  $paramsP['amount'],
			'req_trans_id_merchant' => $paramsP['invoice_no'],
			'req_request_date_time' => date('YmdHis'),
			'req_currency' => '360',
			'req_purchase_currency' => '360',
			'req_session_id' => sha1(date('YmdHis')),
			'req_name' => $paramsP['name'],
			'req_payment_channel' => $paramsP['channel'],
			'req_basket' => $basket,
			'req_address' => "alamat",
			'req_email' => $paramsP['email'],
			'req_token_id' => $paramsP['token']
		);
				return Array("response" => $dokuApi::doPayment($dataPayment), "data_payment" => $dataPayment);
	}

	  private function _get_client_ip(){
          $ipaddress = '';
          if (getenv('HTTP_CLIENT_IP'))
              $ipaddress = getenv('HTTP_CLIENT_IP');
          else if(getenv('HTTP_X_FORWARDED_FOR'))
              $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
          else if(getenv('HTTP_X_FORWARDED'))
              $ipaddress = getenv('HTTP_X_FORWARDED');
          else if(getenv('HTTP_FORWARDED_FOR'))
              $ipaddress = getenv('HTTP_FORWARDED_FOR');
          else if(getenv('HTTP_FORWARDED'))
              $ipaddress = getenv('HTTP_FORWARDED');
          else if(getenv('REMOTE_ADDR'))
              $ipaddress = getenv('REMOTE_ADDR');
          else
              $ipaddress = 'UNKNOWN';

          return $ipaddress;
    }
	
}
