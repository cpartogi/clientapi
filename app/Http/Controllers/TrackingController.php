<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\DB;

class TrackingController extends Controller
{
	
	public function __construct() {
		
	}
	
	public function tracklist(Request $req) {
			
		$notif	= $this->_checkToken($req->json('token'));

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

				$sql = "SELECT id, barcode, 
						case
        					when barcode like 'MAIS%' then 'Matahari Mall'
        					when name like 'lazada%' then 'Lazada'
        					when barcode like 'ASM%' then 'Asmaraku'
        					when barcode like 'FZ%' then 'Frozen Shop'
        					when barcode like 'SO%' then 'SukaOutdoor'
        					when barcode like 'DMS-%' then 'Dimo Shop'
        					when name like 'SPG%' then 'Promo Event'
        					else 'Lain-lain'
    					End as merchant,
    				 	case 
							when taketime='0000-00-00 00:00:00' and overduetime < now() then 'OVERDUE'
        					when status like '%in_store%' then 'IN STORE'
        					when status like '%taken%' then 'CUSTOMER_TAKEN'
						End as status
						from locker_activities where phone='".$phone."' limit ".$offset.", 10";

       			$resp = DB::select($sql);

       			$sqlt = "SELECT count(*) as total FROM locker_activities where phone='".$phone."'";
       			$ttl = DB::select($sqlt);
       			$total = ceil($ttl[0]->total/10);
       			
       			$notif->setOK($total,$resp);
				
			}
		
		return response()->json($notif->build());
	}
	
	public function trackdetail(Request $req) {
			
		$notif	= $this->_checkToken($req->json('token'));

			if( $req->input('id') ) {
				$id = $req->json('id');
			} else {
			   $notif->setDataError();
			}

			if($notif->isOK()) {

				$sql = "SELECT id, barcode, 
						case
        					when barcode like 'MAIS%' then 'Matahari Mall'
        					when name like 'lazada%' then 'Lazada'
        					when barcode like 'ASM%' then 'Asmaraku'
        					when barcode like 'FZ%' then 'Frozen Shop'
        					when barcode like 'SO%' then 'SukaOutdoor'
        					else 'Lain-lain'
    					End as merchant,
    					storetime,
    					taketime,
						overduetime, 
						case 
							when taketime='0000-00-00 00:00:00' and overduetime < now() then 'OVERDUE'
        					when status like '%in_store%' then 'IN STORE'
        					when status like '%taken%' then 'CUSTOMER_TAKEN'
						End as status,
						locker_name    
						from locker_activities where id='".$id."'";
       			$resp = DB::select($sql);
       			$ttl = count($resp);
       			$total = ceil($ttl/10);

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
