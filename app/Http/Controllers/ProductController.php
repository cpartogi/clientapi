<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
	
	public function __construct() {
		
	}
	
	public function prodlist(Request $req) {
			
			$notif	= $this->_checkToken($req->json('token'));
			if( $req->input('page') ) {
            	$page = $req->input('page') - 1;
            	$offset = 10 * $page ;
        	 }	else {
            	$notif->setDataError();
         	}

			if($notif->isOK()) {

				$sql = "SELECT a.sku, a.price, a.discount, a.name, a.description, 
						 CONCAT('http://dimo.clientname.id/assets/images/uploaded/',a.image) AS image, 
						a.max_quantity, a.weight, b.stock, c.category_name
						FROM dimo_products a, dimo_product_stock b, dimo_product_category c 
						where a.id=b.product_id and a.id_category=c.id_category limit ".$offset.", 10";
       			$resp = DB::select($sql);

       			$sqlt = "SELECT count(*) as total FROM dimo_products ";
       			$ttl = DB::select($sqlt);
       			$total = ceil($ttl[0]->total/10);
       			
       			$notif->setOK($total,$resp);
				
			}
		
		
		return response()->json($notif->build());
	}


	public function prodlistcat(Request $req) {
			
			$notif	= $this->_checkToken($req->json('token'));
			if( $req->input('page') && $req->input('category_name')  ) {
				$page = $req->input('page') - 1;
            	$offset = 10 * $page ;
            	$category_name = $req->input('category_name') ;
        	 }	else {
            	$notif->setDataError();
         	}

			if($notif->isOK()) {

				$sql = "SELECT a.sku, a.price, a.discount, a.name, a.description, 
						 CONCAT('http://dimo.clientname.id/assets/images/uploaded/',a.image) AS image, 
						a.max_quantity, a.weight, b.stock, c.category_name
						FROM dimo_products a, dimo_product_stock b, dimo_product_category c 
						where a.id=b.product_id and a.id_category=c.id_category and c.category_name='".$category_name."' 
						limit ".$offset.", 10";
       			$resp = DB::select($sql);

       			$sqlt = "SELECT count(*) as total FROM dimo_products ";
       			$ttl = DB::select($sqlt);
       			$total = ceil($ttl[0]->total/10);
       			
       			$notif->setOK($total,$resp);
				
			}
		
		
		return response()->json($notif->build());
	}


	public function catlist(Request $req){

			$notif	= $this->_checkToken($req->json('token'));
			if( $req->input('page') ) {
            	$page = $req->input('page') - 1;
            	$offset = 10 * $page ;
        	}	else {
            	$notif->setDataError();
         	}

         	if($notif->isOK()) {

				$sql = "SELECT id_category, category_name FROM dimo_product_category limit ".$offset.", 10";
       			$resp = DB::select($sql);

       			$sqlt = "SELECT count(*) as total FROM dimo_product_category";
       			$ttl = DB::select($sqlt);
       			$total = ceil($ttl[0]->total/10);
       			
       			$notif->setOK($total,$resp);
				
			}
		
		
		return response()->json($notif->build());
	
	}
	

	public function anyProdetail(Request $req){

		if ($req->json('token')) {

			$notif	= $this->_checkToken($req->json('token'));
			if( $req->input('sku') ) {
            	$sku = $req->input('sku');
        	}	else {
            	$notif->setDataError();
         	}

         	if($notif->isOK()) {

				$sql = "SELECT a.sku, a.price, a.discount, a.name, a.description, 
						CONCAT('http://dimo.clientname.id/assets/images/uploaded/',a.image) AS image, 
						a.max_quantity, a.weight, b.stock, c.category_name
						FROM dimo_products a, dimo_product_stock b, dimo_product_category c 
						where a.id=b.product_id and a.id_category=c.id_category and a.sku='".$sku."'";   

				$resp = DB::select($sql);
				$ttl = count($resp);
       			$total = ceil($ttl/10);
       			
       			$notif->setOK($total,$resp);
				
			}
		
		
		return response()->json($notif->build());
		} else {
			if( $req->input('sku') ) {
				$sku = $req->input('sku');
			
				$sql = "SELECT a.sku, a.price, a.discount, a.name, a.description, 
						CONCAT('http://dimo.clientname.id/assets/images/uploaded/',a.image) AS image, 
						a.max_quantity, a.weight, b.stock, c.category_name
						FROM dimo_products a, dimo_product_stock b, dimo_product_category c 
						where a.id=b.product_id and a.id_category=c.id_category and a.sku='".$sku."'";   
				$resp = DB::select($sql);
				$data["sku"]=$resp[0]->sku;
				$data["price"]=$resp[0]->price;
				$data["name"]=$resp[0]->name;
				$data["category_name"]=$resp[0]->category_name;
				$data["description"]=$resp[0]->description;
				$data["image"]=$resp[0]->image;
				return view('product.index')->with($data);  
			} else {
				echo " page not found";
			}
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
