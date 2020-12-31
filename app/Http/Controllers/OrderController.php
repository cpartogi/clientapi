<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
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

	public function Orderdetail(Request $req) {
			
		$notif	= $this->_checkToken($req->json('token'));

			if( $req->input('id') ) {
				$id = $req->json('id');
			} else {
			   $notif->setDataError();
			}

			if($notif->isOK()) {

				$sql = "SELECT a.order_id, b.name, a.quantity, a.price, a.discount ,	
				      CONCAT('http://dimo.clientname.id/assets/images/uploaded/',b.image) AS image
				      from order_products a, dimo_products b where a.product_id=b.id and a.order_id='".$id."'";

       			$resp = DB::select($sql);
       			$sqlt = "SELECT count(*) as total FROM order_products where order_id='".$id."'";
       			$ttl = DB::select($sqlt);
       			$total = ceil($ttl[0]->total/10);
       			
       			$notif->setOK($total,$resp);
				
			}
		
		return response()->json($notif->build());
	}
	
	public function Orderhistory(Request $req) {
			
		$notif = $this->_checkToken($req->json('token'));		 

			if( $req->input('phone') ) {
				$phone = $req->json('phone');
			} else {
			   $notif->setDataError();
			}

			if( $req->input('page') ) {
            	$page = $req->input('page') - 1;
            	$offset = 10 * $page ;
        	 }	else {
            	$notif->setDataError();
         	}

			if($notif->isOK()) {

				$sql="SELECT phone,id,invoice_id,address,store_id, 
					    case
        					when status='0' then 'Belum dibayar'
        					when status='1' then 'Lunas'
        				End as status,
						completed_date as order_date FROM orders where phone='".$phone."' order by completed_date desc 
					  	limit ".$offset.", 10";

       			$resp = DB::select($sql);
       			$sqlt = "SELECT count(*) as total FROM orders where phone='".$phone."'";
       			$ttl = DB::select($sqlt);
       			$total = ceil($ttl[0]->total/10);
       			
       			$notif->setOK($total,$resp);
				
			}
		
		return response()->json($notif->build());
	}

	public function Orderdetailinvoice(Request $req) {
			
		$notif = $this->_checkToken($req->json('token'));		 

			if( $req->input('invoice_id') ) {
				$invoice_id = $req->json('invoice_id');
			} else {
			   $notif->setDataError();
			}

			if($notif->isOK()) {

				$sql="SELECT a.phone, a.id, a.invoice_id, a.address, a.store_id, 
					    case
        					when a.status='0' then 'Belum dibayar'
        					when a.status='1' then 'Lunas'
        				End as payment_status,
        				case 
							when b.taketime='0000-00-00 00:00:00' and b.overduetime < now() then 'OVERDUE'
        					when b.status like '%in_store%' then 'IN_STORE'
        					when b.status like '%taken%' then 'CUSTOMER_TAKEN'
        					when b.status is null then 'IN_PROCESS'
						End as status,
						a.completed_date as order_date FROM orders a
						left outer join locker_activities b on a.invoice_id=b.barcode
						where a.invoice_id='".$invoice_id."'";

       			$resp = DB::select($sql);
       			$total = 1;
       			
       			$notif->setOK($total,$resp);
				
			}
		
		return response()->json($notif->build());
	}


	public function Ordersubmit(Request $req) {
		$notif = $this->_checkToken($req->json('token'));	

			if( $req->input('phone') && $req->input('total_amount') && $req->input('address') ) {
				$phone = $req->json('phone');
				$amount = $req->json('total_amount');
				$address = $req->json('address');
			} else {
			   $notif->setDataError();
			    return response()->json($notif->build());
			}


			if($notif->isOK()) {

			$data = $req->json("data");	
			$trans_id = time();
		
			$invoice_id = "SHOP-".date('Y').date('m').date('d').date('H').date('i').rand(1,999);

		 	$ins = array(
            	'status' => 0,
            	'deliver_status' => 0,
            	'check_out_date' => date("Y-m-d H:i:s"),
            	'completed_date' => date("Y-m-d H:i:s"),
            	'trans_id' => $trans_id,
            	'invoice_id' => $invoice_id,
            	'amount' => $amount,
            	'phone' => $phone,
            	'address' => $address,
            	'store_id' => 'Popshop');

        	$res = DB::table('orders')->insertGetId($ins);
       
      			foreach ($data as $datas)
				{		

					$sqlprod = "select id, name from dimo_products where sku ='".$datas['sku']."'";
					$prod_id = DB::select($sqlprod);
					$product_id = $prod_id[0]->id;
					$product_name = $prod_id[0]->name;

					//get current stock
					$sqlstock = "select stock from dimo_product_stock where product_id='".$product_id."'";
					$rstock = DB::select($sqlstock);
					$cstock = $rstock[0]->stock;

					if ($cstock == "0") {
					

						$resp=['response' => ['code' => '300','message' =>"product ".$product_name." is out of stock"], 'data' => []];
						return response()->json($resp);
						DB::table('orders')->where('trans_id', $trans_id)->delete();
					} 

					if ($cstock < $datas['quantity']) {
						$resp=['response' => ['code' => '301','message' =>"product ".$product_name." has limited stock please reduce order quantity"], 'data' => []];
						return response()->json($resp);
						DB::table('orders')->where('trans_id', $trans_id)->delete();
					}
					
					if ($cstock > $datas['quantity'] ) {
					$stock = $cstock - $datas['quantity'];

					//reduce stock	
					DB::table('dimo_product_stock')
                    ->where('product_id', $product_id)
                    ->update(array(
		                    'stock' => $stock
                    ));   


				 	//insert to database
				 	DB::table('order_products')
                    ->insert([
                            'order_id' => $res,
                            'product_id' => $product_id,
                            'quantity'  => $datas['quantity'], 
                            'price'     => $datas['price'],
                            'discount' => $datas['discount']
                	]); 

					//get member gcm
					$sqlgcm = "select member_gcm_token from tb_member where phone='".$phone."'";
					$rsgcm = DB::select($sqlgcm);
					$gcm = $rsgcm[0]->member_gcm_token; 
					


                    $urlupload = "https://gcm-http.googleapis.com/gcm/send";
                    
       				$this->headers[]="Authorization:key=".env('GCM_KEY');

       				$varupload = [
           					'data' => ['service'=>'popshop', 'detail'=> ['invoice_id'=> $invoice_id], 'title'=>'Terima kasih Anda telah berbelanja di PopShop, segera lakukan pembayaran'],
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

					}


				}

       		}

			$resp=['response' => ['code' => '200','message' =>"order update data success"], 'data' => []];
			return response()->json($resp);
    	
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
