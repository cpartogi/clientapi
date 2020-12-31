<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\DB;
use App\Users;

class BalanceController extends Controller
{
	
	public function __construct() {
		
	}	

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


	public function Balanceinfo(Request $req) {
			
		$notif	= $this->_checkToken($req->json('token'));

			if( $req->input('phone')  ) {
				$phone = $req->json('phone');
			} else {
			   $notif->setDataError();
			   return response()->json($notif->build());
			}

			if($notif->isOK()) {
				//cek user balance
				$sqlbal = "Select current_balance from tb_member_balance where phone='".$phone."' order by last_update desc limit 0,1";
        		$bl = DB::select($sqlbal);
        		$blc = count($bl);

        			if ($blc != "0") { 
        				$current_balance = $bl[0]->current_balance;
        			} else {
        				$current_balance = "0";	
        			} 
				//$resp=['response'=>['current_balance' => $current_balance]];
				$resp=['response' => ['code' => '200','message' =>"OK"], 'data' => [['current_balance'=> $current_balance]]]; 
			}
		
		return response()->json($resp);
	}

	public function Balancehistory(Request $req) {
			
		$notif	= $this->_checkToken($req->json('token'));

			if( $req->input('phone') && $req->input('page')  ) {
				$phone = $req->json('phone');
				$page = $req->input('page') - 1;
            	$offset = 10 * $page ;
			} else {
			   $notif->setDataError();
			   return response()->json($notif->build());
			}

			if($notif->isOK()) {
				
				$sqlbal = "Select top_up_amount, deduction_amount, prev_balance, current_balance, last_update from tb_member_balance where phone='".$phone."' order by last_update desc limit ".$offset.",10";
        		$resp = DB::select($sqlbal);
        		$blc = count($resp);
        		$total = ceil($blc/10);

       			$notif->setOK($total,$resp);
				
			}
		
		return response()->json($notif->build());
	}




	public function Balancetopup(Request $req) {	
		$ipRemoteFrom = self::_get_client_ip();		
		if (!in_array($ipRemoteFrom, config('config.ip_access'))){           
            die("Api not found");
        }  
		$notif	= $this->_checkToken($req->json('token'));		
			if( $req->input('top_up_amount') && $req->input('phone') && $req->input('type') && $req->input('transid')) {

				$top_up_amount = $req->json('top_up_amount');
				$phone = $req->json('phone');
				$type = $req->json('type');
				$transid = $req->json('transid');				
				$isValidTopup = self::_isValidTopup($type, $transid, $phone);
				if (!$isValidTopup){
					die("Topup not permition");
				}				
			} else {
			   $notif->setDataError();
			    return response()->json($notif->build());
			}

			

			if($notif->isOK()) {

				// check if user have balance before
				$sqlbal = "Select current_balance from tb_member_balance where phone='".$phone."' order by last_update desc limit 0,1";
        		$bl = DB::select($sqlbal);
        		$blc = count($bl);

        		if ($blc != 0) {
        			$prev_balance = $bl[0]->current_balance;
        			$current_balance = $prev_balance+$top_up_amount;
     				DB::table('tb_member_balance')
                    ->insert(['phone' => $phone,
                    		'payment_type' => $type,
                            'id_transaction' => $transid,
                            'top_up_amount' => $top_up_amount,                            
                            'prev_balance'  => $prev_balance, 
                            'current_balance'     => $current_balance,
                            'last_update' => date('Y-m-d H:i:s')
                	]);

                    //get member gcm
					$sqlgcm = "select member_gcm_token from tb_member where phone='".$phone."'";					
					$rsgcm = DB::select($sqlgcm);
					$gcm = $rsgcm[0]->member_gcm_token; 
					

					 //get member gcm
                    $urlupload = "https://gcm-http.googleapis.com/gcm/send";
                    
       				$this->headers[]="Authorization:key=".env('GCM_KEY');

       				$varupload = [
           					'data' => ['service'=> 'balance top up', 'detail' => ['balance_amount'=> $current_balance], 'title'=> 'Saldo anda telah bertambah'],
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

                $resp=['response' => ['code' => '200','message' =>"top up balance success"], 'data' => [['current_balance'=> $current_balance]]]; 	    
				
				} else {
					DB::table('tb_member_balance')
                    ->insert(['phone' => $phone,
                    		'payment_type' => $type,
                            'id_transaction' => $transid,
                            'top_up_amount' => $top_up_amount,
                            'prev_balance'  => 0, 
                            'current_balance'  => $top_up_amount,
                            'last_update' => date('Y-m-d H:i:s')
                	]);

                        //get member gcm
					$sqlgcm = "select member_gcm_token from tb_member where phone='".$phone."'";
					$rsgcm = DB::select($sqlgcm);
					$gcm = $rsgcm[0]->member_gcm_token; 
					
					$urlupload = "https://gcm-http.googleapis.com/gcm/send";
                    
       				$this->headers[]="Authorization:key=".env('GCM_KEY');

       				$varupload = [
           					'data' => ['service'=> 'balance top up', 'detail' => ['balance_amount'=> $top_up_amount], 'title'=> 'Saldo anda telah bertambah'],
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


			    $resp=['response' => ['code' => '200','message' =>"top up balance success"], 'data' => [['current_balance'=> $top_up_amount]]]; 
				}
				
			}
		
		return response()->json($resp);
	}
	
	public function Balancededuct(Request $req) {	
			$ipRemoteFrom = self::_get_client_ip();		
			if (!in_array($ipRemoteFrom, config('config.ip_access'))){           
	            die("Api not found");
	        } 
			$notif	= $this->_checkToken($req->json('token'));			
			if( $req->input('deduct_amount') && $req->input('phone') 
				&& $req->input('invoice_id') && $req->json('uid_token')) {
				$deduct_amount = $req->json('deduct_amount');
				$invoice_id = $req->json('invoice_id');
				$phone = $req->json('phone');
				$uid_token = $req->json('uid_token');				
				$user = Users::checkTokenUser($phone, $uid_token);										
				if (empty($user)){		
					die("User not found");
				}				
			} else {
			   $notif->setDataError();
			   return response()->json($notif->build());
			}

			if($notif->isOK()) {

				// check if user have balance before
				$sqlbal = "Select current_balance from tb_member_balance where phone='".$phone."' order by last_update desc limit 0,1";
        		$bl = DB::select($sqlbal);
        		$blc = count($bl);

        		if ($blc != 0) {
        			$prev_balance = $bl[0]->current_balance;
        			$current_balance = $prev_balance-$deduct_amount;
     				DB::table('tb_member_balance')
                    ->insert(['phone' => $phone,
                            'deduction_amount' => $deduct_amount,
                            'prev_balance'  => $prev_balance, 
                            'current_balance'     => $current_balance,
                            'invoice_id' => $invoice_id,
                            'last_update' => date('Y-m-d H:i:s'),
                	]);
                	
                    //get member gcm
					$sqlgcm = "select member_gcm_token from tb_member where phone='".$phone."'";
					$rsgcm = DB::select($sqlgcm);
					$gcm = $rsgcm[0]->member_gcm_token; 
					


                    $urlupload = "https://gcm-http.googleapis.com/gcm/send";
                    
       				$this->headers[]="Authorization:key=".env('GCM_KEY');

       				$varupload = [
           					'data' => ['service'=> 'balance deduction', 'detail' => ['balance_amount'=> $current_balance], 'title'=> 'Saldo anda telah berkurang'],
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


                 $resp=['response' => ['code' => '200','message' =>"deduct balance success"], 'data' => [['current_balance'=> $current_balance ]]]; 	      
				} else {
					DB::table('tb_member_balance')
                    ->insert(['phone' => $phone,
                            'deduction_amount' => $deduct_amount,
                            'prev_balance'  => 0, 
                            'current_balance'  => -$deduct_amount,
                            'invoice_id' => $invoice_id,
                            'last_update' => date('Y-m-d H:i:s')
                	]);

                        //get member gcm
					$sqlgcm = "select member_gcm_token from tb_member where phone='".$phone."'";
					$rsgcm = DB::select($sqlgcm);
					$gcm = $rsgcm[0]->member_gcm_token; 
					

					 //get member gcm
                    $urlupload = "https://gcm-http.googleapis.com/gcm/send";
                    
       				$this->headers[]="Authorization:key=".env('GCM_KEY');

       				$varupload = [
           				'data' => ['service'=> 'balance deduction', 'detail' => ['balance_amount'=> -$deduct_amount ], 'title'=> 'Saldo anda telah berkurang'],
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

                 $resp=['response' => ['code' => '200','message' =>"deduct balance success"], 'data' => [['current_balance'=> -$deduct_amount]]]; 	    
				}
			}
		
		return response()->json($resp);
	}
	
	public function BalanceBonus(Request $req) {
			
		$notif	= $this->_checkToken($req->json('token'));

		if($req->input('phone')) {
				$phone = $req->json('phone');
		} else {
			   $notif->setDataError();
			   return response()->json($notif->build());
		}

		if($notif->isOK()) {

				$sql = "SELECT bonus_amount from tb_bonus_balance where expired_date > now() "; 
				//echo $sql;		
				$resp = DB::select($sql);    
				$total = count($resp);			
       		    if ($total == 0) {
       		    	$bonus_amount="0";
       		    } else {
       		    	$bonus_amount=$resp[0]->bonus_amount;
       		    }

       		   
       			
       			$res = ['response' => ['code' => '200','message' =>"OK"], 'bonus_receiver'=>$phone, 'bonus_amount'=>$bonus_amount];
			}
		return response()->json($res);


	}	

	public function balancepackage(Request $req) {
    	$notif	= $this->_checkToken($req->json('token'));

    	if($notif->isOK()) {
    		$sql="select id_package, package_name, package_description, package_value from tb_topup_package ";
			$resp = DB::select($sql);    

			$res =  ['response' => ['code' => '200','message' =>"OK"], 'data'=>$resp];
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

	/*
		Valid jika transactioncc ada tetapi balancenya belum ada atau 
	*/
	private function _isValidTopup($type, $transid, $phone){
		$isValidTopup = false;
		$transactioncc =  DB::table("doku_cc_settlement")
		->where("transidmerchant", $transid)
		->where("response_code", "0000")
		->first();					
		if (isset($transactioncc)){
			$trans_balance_top =  DB::table("tb_member_balance")
			->where("id_transaction", "=",  $transid)
			->where("payment_type", "=", $type)
			->where("phone", "=", $phone)
			->first();						
			if (!isset($trans_balance_top)){				
				$isValidTopup = true;
			}
		}
		return $isValidTopup;
		
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
