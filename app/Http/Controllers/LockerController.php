<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\DB;

use App\Http\Helpers\NotificationHelper;
use App\Http\Helpers\WebCurl;

//models
use App\CompanyToken;


class LockerController extends Controller
{
    
	public function getLocation (Request $req) {
		$notif = $this->_checkToken($req->json('token'));

		if($req->input('country') != "" || $req->input('province') != "" || $req->input('city') != "" ) {
				$country = $req->json('country');
				$province = $req->json('province');
				$city = $req->json('city');
				$zip_code = $req->json('zip_code');
		} else {
			   $notif->setDataError();
			   return response()->json($notif->build());
		}

		if($notif->isOK()) {
			
				$sql = "Select a.locker_id as id, a.name, a.address, a.address_2, a.zip_code, a.latitude, 
				        a.longitude, b.district as city, c.province, d.country, a.operational_hours, a.locker_capacity from 
				        locker_locations a, districts b, provinces c, countries d where a.district_id=b.id 
				        and b.province_id=c.id and c.country_id=d.id and d.country like '%$country%' 
				        and c.province like '%$province%' and b.district like '%$city%' and a.zip_code like '%$zip_code%' "; 
				

				$resp = DB::select($sql);    			
       		    $total = count($resp);
			    $res = ['response'=> ['code' => '200', 'message' => 'OK'] ,'Filter' => ['country' => $country, 'province' => $province, 'city' =>$city, 'zip_code' => $zip_code  ], 'total_data'=> $total, 'data'=>$resp];
       		    return response()->json($res);
		}	

		      

	}


	public function getCountry (Request $req) {
		$notif = $this->_checkToken($req->json('token'));

		if($notif->isOK()) {
			
				$sql = "Select country from countries order by country"; 
				$resp = DB::select($sql);    			
       		    $total = count($resp);
			    $res = ['response'=> ['code' => '200', 'message' => 'OK'] ,'total_data'=> $total, 'data'=>$resp];
       		    return response()->json($res);
		}	

	}

	public function getProvince (Request $req) {
		$notif = $this->_checkToken($req->json('token'));
		        $country = $req->json('country');

		if($notif->isOK()) {
			
				$sql = "Select a.province as province from provinces a, countries b where a.country_id=b.id and b.country like '%$country%' order by province"; 
				$resp = DB::select($sql);    			
       		    $total = count($resp);
			    $res = ['response'=> ['code' => '200', 'message' => 'OK'] ,'total_data'=> $total, 'data'=>$resp];
       		    return response()->json($res);
		}	

	}

	public function getCity (Request $req) {
		$notif = $this->_checkToken($req->json('token'));
		 		$country = $req->json('country');
		  		$province = $req->json('province');

		if($notif->isOK()) {
			
				$sql = "Select a.district as city from districts a, provinces b, countries c where a.province_id=b.id and
						b.country_id=c.id and c.country like '%$country%' and b.province like '%$province%'
						 order by district"; 
				$resp = DB::select($sql);    			
       		    $total = count($resp);
			    $res = ['response'=> ['code' => '200', 'message' => 'OK'] ,'total_data'=> $total, 'data'=>$resp];
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
