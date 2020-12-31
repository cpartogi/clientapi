<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\DB;
use App\Http\Helpers\WebCurl;

class OtoController extends Controller
{	
	var $curl;
	
	public function __construct() {
		
	}	
	public function Category(Request $req) {
	
			$notif	= $this->_checkToken($req->json('token'));

			if( $req->input('page')  ) {
				$page = $req->json('page');
			} else {
			   $notif->setDataError();
		
			}

			if($notif->isOK()) {
				
				$sqlcat = "select id_category,category_name from tb_o2o_category order by id_category";
        		$bl = DB::select($sqlcat);
        		$total = 1; 

        		$notif->setOK($total,$bl);		
			}
		
		return response()->json($notif->build());
	}	


	public function product(Request $req) {
			
		$notif	= $this->_checkToken($req->json('token'));

			if( $req->input('page')  ) {
				$page = $req->input('page') - 1;
            	$offset = 10 * $page ;
			} else {
			   $notif->setDataError();
			} 

			if( $req->input('id_category')  ) {
				$id_category=$req->input('id_category');				
			} else {
			   $notif->setDataError();
			  
			}

			if($notif->isOK()) {
				
				$sqlpro = "select a.category_name,b.product_sku,b.product_name,b.product_unit_price
				from tb_o2o_category a inner join tb_o2o_product b where a.id_category='".$id_category."' limit ".$offset.", 10";
        		$resp = DB::select($sqlpro);
        		$blc = count($resp);
        		$total = ceil($blc/10);

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
		
				$invoice_id = "O2O-".date('Y').date('m').date('d').date('H').date('i').rand(1,999);

				$ins = array(
            	'phone' => $phone,
            	'status' => 0,
            	'trans_id' => $trans_id,
            	'invoice_id' => $invoice_id,
            	'amount' => $amount,
             	'address' => $address,
            	'order_date' => date("Y-m-d H:i:s"));

        		$res = DB::table('tb_o2o_order')->insertGetId($ins);


        		foreach ($data as $datas)
				{		

					$sqlprod = "select id_product from tb_o2o_product where product_sku ='".$datas['sku']."'";
					$prod_id = DB::select($sqlprod);

					$id_product = $prod_id[0]->id_product;

				 	DB::table('tb_o2o_order_product')
                    ->insert([
                            'id_order' => $res,
                            'id_product' => $id_product,
                            'quantity'  => $datas['quantity'], 
                            'price'     => $datas['price']
                        ]);
					
				}

       			$resp=['response' => ['code' => '200','message' =>"O2O order submit success"], 'data' => [['total_amount'=> $amount,'invoice_id'=>$invoice_id]]];
       			return response()->json($resp);
			}
		
	}
	

	public function Orderhistory(Request $req) {
			
		$notif = $this->_checkToken($req->json('token'));		 

			if( $req->input('phone') && $req->input('page') ) {
				$phone = $req->json('phone');
				$page = $req->input('page') - 1;
            	$offset = 10 * $page ;
			} else {
			   $notif->setDataError();
			   return response()->json($notif->build());
			}

			if($notif->isOK()) {

				$sql="SELECT phone,id_order,invoice_id,amount,address, 
					  case
        					when status='0' then 'Belum dibayar'
        					when status='1' then 'Lunas'
        				End as status,
        			  order_date	
					  FROM tb_o2o_order where phone='".$phone."' 
					  limit ".$offset.", 10";

       			$resp = DB::select($sql);
       			$sqlt = "SELECT count(*) as total FROM tb_o2o_order where phone='".$phone."'";
       			$ttl = DB::select($sqlt);
       			$total = ceil($ttl[0]->total/10);
       			
       			$notif->setOK($total,$resp);
				
			}
		
		return response()->json($notif->build());
	}


	public function Orderdetail(Request $req) {
			
		$notif	= $this->_checkToken($req->json('token'));

			if( $req->input('id_order') ) {
				$id = $req->json('id_order');
			} else {
			   $notif->setDataError();
			}

			if($notif->isOK()) {

				$sql = "SELECT a.id_order, c.category_name, b.product_name, a.quantity, a.price from tb_o2o_order_product 	  a, tb_o2o_product b, tb_o2o_category c 
					    where a.id_product=b.id_product and b.id_category=c.id_category and a.id_order='".$id."'";

       			$resp = DB::select($sql);
       			$total = 1;
       			
       			$notif->setOK($total,$resp);
				
			}
		
		return response()->json($notif->build());
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
