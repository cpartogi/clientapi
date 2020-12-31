<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\DB;

class MerchantController extends Controller
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


	public function pickup(Request $req) {
		$notif = $this->_checkToken($req->json('token'));	

			if($req->input('order_no') && $req->input('phone') && $req->input('popbox_location')  && $req->input('pickup_location') ) {
				$token = $req->json('token');
				$merchant_name = $req->json('merchant_name');
				$order_number = $req->input('order_no');
				$customer_phone = $req->input('phone');
				$popbox_location = $req->input('popbox_location');
				$pickup_location = $req->input('pickup_location');
			} else {
			   $notif->setDataError();
			    return response()->json($notif->build());
			}


			if($notif->isOK()) {

				$sqlmerchant="SELECT name , company_type FROM companies WHERE id in(SELECT company_id FROM api_company_access_tokens
                WHERE access_token='".$token."')";
				$mc=DB::select($sqlmerchant);
				
					// if merchant type = aggregator merchant name is mandatory
					if ($mc[0]->company_type == "3") {
						if($req->json('merchant_name')) {
							$merchant_name = $req->json('merchant_name');
						} else  {
							$notif->setDataError();
			    			return response()->json($notif->build());
						}
					} else {
						$merchant_name = $mc[0]->name ;
					}

					DB::table('tb_merchant_pickup')
                    ->insert([
                            'merchant_name' => $merchant_name,
                            'order_number' => $order_number,
                            'customer_phone' => $customer_phone,
                            'popbox_location'  => $popbox_location,
                            'pickup_location' => $pickup_location, 
                            'customer_email' => $req->input('customer_email'),
                            'detail_items' => $req->input('detail_items'),
                            'price' => $req->input('price'),	
                            'last_update' => date("Y-m-d H:i:s")
                	]);
					

           		$username = '082113114191';
        		$password = "888888";

        		$urlogin = env('API_URL')."/ebox/api/v1/user/login";
        		$varlogin = [
          					'loginName' => $username,
            				'password' => $password
        					];
     
       			$logres=$this->post_data($urlogin,json_encode ( $varlogin ), null);
        		$api_token=$logres['token'];

        		$urlupload = env('API_URL')."/ebox/api/v1/express/import";
       			$this->headers[]="userToken:".$api_token;

       			$varupload = [
           					'expressNumber' => $order_number,
         					'takeUserPhoneNumber' => $customer_phone
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
			
				$res=['response' => ['code' => '200','message' =>"success submit pick up data"], 'data' =>[]];
       		 	return response()->json($res);

       		}
	}
	

	public function cashonpickup(Request $req) {
			$notif = $this->_checkToken($req->json('token'));	

			if($req->input('order_no') && $req->input('customer_phone') && $req->input('customer_email')  && $req->input('popbox_address') &&  $req->input('order_amount') ) {
				$token = $req->json('token');
				$order_number = $req->input('order_no');
				$customer_phone = $req->input('customer_phone');
				$customer_email = $req->input('customer_email');
				$popbox_address = $req->input('popbox_address');
				$order_amount = $req->input('order_amount');
			} else {
			    $notif->setDataError();
			    return response()->json($notif->build());
			}

			
			if($notif->isOK()) {
			
				$sqlmerchant="SELECT name , company_type FROM companies WHERE id in(SELECT company_id FROM api_company_access_tokens
                WHERE access_token='".$token."')";
				$mc=DB::select($sqlmerchant);
				
					// if merchant type = aggregator merchant name is mandatory
					if ($mc[0]->company_type == "3") {
						if($req->json('store_name')) {
							$store_name = $req->json('store_name');
						} else  {
							$notif->setDataError();
			    			return response()->json($notif->build());
						}
					} else {
						$store_name = $mc[0]->name ;
					}



					$order_amount= str_replace(',', '', $order_amount);
        			$order_amount= str_replace(' ', '', $order_amount);
        			$order_amount= str_replace('-', '', $order_amount);
        			$order_amount= str_replace('.', '', $order_amount);
        			$order_amount= str_replace('Rp', '', $order_amount);

        			//cek if already exist 
        			$sql	= "SELECT order_number FROM tb_cashier_transaction where 
        				       order_number='".$order_number."'";
					$res	= DB::select($sql);
					$rest	= count($res);

					if ($rest == 0 ) {

					$kode_barcode = rand(10,100).substr(microtime(),2,3);
					$rupiah=str_pad($order_amount,7,"0",STR_PAD_LEFT);
					$plu=700645;
					$barcode="0".$plu."0".$kode_barcode.$rupiah;	


				
        			//insert to table transaction
				    DB::table('tb_cashier_transaction')
							->insert([
								'order_number'=> $order_number,
								'customer_phone'=> $customer_phone,
								'customer_email'=> $customer_email, 
								'order_detail_items'=> $req->input('order_detail_items'),
								'store_name' => $store_name,
								'popbox_address'=> $popbox_address,
								'popbox_barcode'=>$barcode,
								'order_amount' => $order_amount,
								'order_date' => date("Y-m-d H:i:s")
					]);
					
					//insert to table barcode generator
					  DB::table('tb_transaction_barcode')
							->insert([
								'order_number'=> $order_number,
								'kode_barcode'=> $kode_barcode,
								'barcode' => $barcode,
								'generate_date' => date("Y-m-d H:i:s")
					]);


						


					$username = '082113114191';
        			$password = "888888";

        			$urlogin =  env('API_URL')."/ebox/api/v1/user/login";
        			$varlogin = [
            					'loginName' => $username,
            					'password' => $password
        			];
     
        			$logres=$this->post_data($urlogin,json_encode ( $varlogin ), null);
        			$api_token=$logres['token'];

        			$urlupload = env('API_URL')."/ebox/api/v1/express/import";
       				$this->headers[]="userToken:".$api_token;
	
       				$varupload = [
           					'expressNumber' => $order_number,
         					'takeUserPhoneNumber' => $customer_phone,
         					'groupName' => 'COD'
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

       		 	$res=['response' => ['code' => '200','message' =>"success submit cash on pick up data"], 'data' =>[]];
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
	
}
