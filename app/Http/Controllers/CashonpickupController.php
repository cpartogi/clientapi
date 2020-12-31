<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\DB;
use App\Http\Helpers\WebCurl;
use App\Http\Helpers\SmsSender;

use App\CashierTrasaction;

class CashonpickupController extends Controller
{	
	
	var $curl;
	public function __construct() {
		$headers    = ['Content-Type: application/json'];
        $this->curl = new WebCurl($headers);
		
	}	
	public function OrderDetail(Request $req) {
			
		$notif = $this->_checkToken($req->json('token'));

			if($req->input('order_number') ) {
				$order_number = $req->json('order_number');
				} else {
			   $notif->setDataError();
			   return response()->json($notif->build());
			}


			if($notif->isOK()) {
				//Get data
				$sql = "select order_number, store_name, customer_phone, customer_email, popbox_address, 
						order_amount, order_date from tb_cashier_transaction where order_number='".$order_number."'";
				$resp = DB::Select($sql);
				$total = count($resp);
       			
				if ($total == 0 ) {
					$res=['response' => ['code' => '200','message' =>"NOT AVAILABLE"], 'data' =>[]];
				} else {
       				//cek if order have been paid
       				$sqlpaid = "select order_number, store_name, customer_phone, customer_email, popbox_address, 
						order_amount, transaction_date as order_date from tb_cashier_transaction where order_number='".$order_number."' and payment_type='Paid'";
       				$respaid = DB::select($sqlpaid);
       				$totalp = count($respaid);
       					if($totalp != 0) {
       						$res=['response' => ['code' => '200','message' =>"PAID"], 'data' =>$respaid];
       					} else {
	       					$res=['response' => ['code' => '200','message' =>"AVAILABLE"], 'data' =>$resp];
    					}   			
       			}
       		 	return response()->json($res);
			}
		  
	}
	
	public function OrderSubmit(Request $req) {
			
		$notif = $this->_checkToken($req->json('token'));

			if($req->json('order_number') && $req->json('settle_code') && $req->json('order_amount') ) {
				$order_number = $req->json('order_number');
				$order_amount = $req->json('order_amount');
				$settle_code = $req->json('settle_code');
				$settle_place = $req->json('settle_place');
				$settle_timestamp = $req->json('settle_timestamp');
				} else {
			   $notif->setDataError();
			   return response()->json($notif->build());
			}

			if($notif->isOK()) {
				 DB::table('tb_cashier_transaction')
                    ->where('order_number', $order_number)
                    ->update(array(
                    'payment_type' => 'Paid',
                    'transaction_date' => date("Y-m-d H:i:s")
                    ));           

                $tid = strlen($settle_code) > 42 ? substr($settle_code, 34, 8) : $settle_code;    

                DB::table('tb_bank_settlement')
                    ->insert([
                            'order_number' => $order_number,
                            'order_amount' => $order_amount,
                            'terminal_id' => $tid,
                            'settle_code' => $settle_code,
                            'settle_place' => $settle_place,
                            'settle_timestamp' => $settle_timestamp,
                            'submit_date'     => date("Y-m-d H:i:s")
                ]);
				
				$cashierTrx = CashierTrasaction::where('order_number', $order_number)->first();
				if($cashierTrx != null) {
					$cn = strlen($settle_code) > 15 ? substr($settle_code, 0, 16) : $settle_code;
				//	$tid = strlen($settle_code) > 42 ? substr($settle_code, 34, 8) : $settle_code;
					$saldo = strlen($settle_code) > 42 ? substr($settle_code, 54, 8) : $settle_code;
					$saldo = ltrim($saldo, '0');;
					$bs_msg = 'Pembayaran sukses sejumlah Rp '.$order_amount.' pada '.@date('d-m-y H:i:s', @strtotime($settle_timestamp)).', di Popbox @ '.$settle_place.', ';
					$bs_msg .= 'CN:'.$cn.', TID:'.$tid.", Saldo: Rp ".$saldo;
					(new SmsSender($cashierTrx->customer_phone, $bs_msg))->send();
				}

				//send pin via sms using jatis
				$sql = "Select phone, validatecode, locker_name, overduetime from locker_activities 
						where barcode = '".$order_number."' and status='IN_STORE'";
				$res = DB::select($sql);

				$phone = $res[0]->phone;
				$pin = $res[0]->validatecode;
				$locker_name = $res[0]->locker_name;
				$odtime = date("d/m/y", strtotime($res[0]->overduetime));


				$message = "Order No: ".$order_number." sudah bisa diambil di Popbox @ ".$locker_name.". Kode ambil ".$pin." . CS: 02129022537 www.clientname.id";

				$sms = json_encode(['to' => $phone,
						'message' => $message,
						'token' => '72472b3AW3WEG3249i239565Ddfg5'
						]);

				$urlsms = "http://smsdev.clientname.id/sms/send";

				$resp = json_decode($this->curl->post($urlsms, $sms), true);

           	 // insert partner response to database
            	DB::table('companies_response')
                    ->insert([
                            'api_url' => $urlsms,
                            'api_send_data' => $sms,
                            'api_response'  => json_encode($resp), 
                            'response_date'     => date("Y-m-d H:i:s")
                ]);
       		   $res=['response' => ['code' => '200','message' =>"SUCCESS"]];
             } else {
             	$res=['response' => ['code' => '300','message' =>"FAILED"]];

             }       

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
