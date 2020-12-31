<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\DB;
use App\Http\Helpers\WebCurl;

class ParcelController extends Controller
{	
	var $curl;
	
	public function __construct() {
		
	}	
	public function parcelstatus(Request $req) {
			
		$notif = $this->_checkToken($req->json('token'));

			if( $req->json('order_number') ) {
				$order_number = $req->json('order_number');
				$type = $req->json('type');
			} else {
			   $notif->setDataError();
			   return response()->json($notif->build());
			}

			if($notif->isOK()) {

				$sql = "Select barcode as order_number, locker_name, storetime , taketime, status from locker_activities
				where barcode='$order_number'";
				
				$resp = DB::select($sql);    			
       		    $total = count($resp);
       		    
       		    if ($total == '0') {
       		    	$res = ['response' => ['code' => '200','message' =>"OK"], 'data'=>'Not Found'];
       		    	 return response()->json($res);
       		    } else {
       				$res = ['response' => ['code' => '200','message' =>"OK"], 'data'=>$resp];
       				return response()->json($res);
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
