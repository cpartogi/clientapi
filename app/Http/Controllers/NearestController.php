<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\DB;
use App\Http\Helpers\WebCurl;

class NearestController extends Controller
{	
	var $curl;
	
	public function __construct() {
		
	}	
	public function nearestlist(Request $req) {
			
		$notif = $this->_checkToken($req->json('token'));

			if( $req->input('latitude') ) {
				$latitude = $req->json('latitude');
			} else {
			   $notif->setDataError();
			   return response()->json($notif->build());
			}

			if( $req->input('longitude')){
				$longitude=$req->json('longitude');
				} else {
					$notif->setDataError();
					return response()->json($notif->build());
				}

			if($notif->isOK()) {

				$sql = "SELECT name, address, address_2, latitude, longitude, SQRT(
    			POW(69.1 * (latitude - (".$latitude.")), 2) +
    			POW(69.1  *(".$longitude." - longitude) * COS(latitude / 57.3), 2)) AS distance
				FROM locker_locations HAVING distance < 2 ORDER BY distance";
				
				$resp = DB::select($sql);    			
       		    $total = count($resp);
       			
       			$res = ['response' => ['code' => '200','message' =>"OK"], 'total_data'=> $total, 'data'=>$resp];
			}
		  return response()->json($res);
	}
	
	public function nearestsearch(Request $req) {
		$notif = $this->_checkToken($req->json('token'));

			if( $req->input('keywords') ) {
				$keyword = $req->json('keywords');
			} else {
			   $notif->setDataError();
			   return response()->json($notif->build());
			}

			if($notif->isOK()) {

				
				
				$sql = "SELECT a.name, a.address, a.address_2, b.district as city, c.province, a.latitude, a.longitude , 
				         a.operational_hours
						from locker_locations a, districts b, provinces c
						where a.district_id=b.id and c.id=b.province_id and c.country_id='1'
						and (a.name like '%".trim($keyword)."%' or a.address like '%".trim($keyword)."%')"; 
				
				//echo $sql;		
				
				$resp = DB::select($sql);    			
       		    $total = count($resp);
       			
       			$res = ['response' => ['code' => '200','message' =>"OK"], 'total_data'=> $total, 'data'=>$resp];
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
