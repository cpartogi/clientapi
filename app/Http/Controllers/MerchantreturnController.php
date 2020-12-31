<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\DB;

class MerchantreturnController extends Controller
{
	
	public function __construct() {
		
	}
	
	public function Merchantsubmit(Request $req) {
		$notif = $this->_checkToken($req->json('token'));	

			if($req->input('no_order') && $req->input('phone') ) {
				$token = $req->json('token');
				$no_order = $req->json('no_order');
				$phone = $req->json('phone');
				$seller_address = $req->json('seller_address');
				$expired_date = $req->json('expired_time');
			} else {
			   $notif->setDataError();
			    return response()->json($notif->build());
			}


			if($notif->isOK()) {

				$sqlmerchant="SELECT name FROM companies WHERE id in(SELECT company_id FROM api_company_access_tokens
                WHERE access_token='".$token."')";
				$mc=DB::select($sqlmerchant);
				
				$data = $req->json("data");	
				
				if (is_null($data)){
					DB::table('tb_merchant_return')
                   		 ->insert([
                            'merchant_name' => $mc[0]->name,
                            'phone' => $phone,
                            'barcode' => $no_order,
                            'seller_address' => $seller_address,
                            'expired_date' => $expired_date,
                            'update_date' => date("Y-m-d H:i:s")
                		]);
				} else {

					foreach ($data as $datas)
					{		

						DB::table('tb_merchant_return')
                   		 ->insert([
                            'merchant_name' => $mc[0]->name,
                            'phone' => $phone,
                            'barcode' => $no_order,
                            'seller_address' => $seller_address,
                            'expired_date' => $expired_date,
                            'sku'  => $datas['sku'], 
                            'item_desc'     => $datas['item_desc'],
                            'item_quantity' => $datas['item_quantity'],
                            'update_date' => date("Y-m-d H:i:s")
                		]);
					}
				}
				
				$res=['response' => ['code' => '200','message' =>"success submit return data"], 'data' =>[]];
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
