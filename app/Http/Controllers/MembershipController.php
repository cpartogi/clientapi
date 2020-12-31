<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\DB;
use App\Http\Helpers\WebCurl;
use App\Users;

class MembershipController extends Controller
{
	 var $curl;
	public function __construct() {
		$headers    = ['Content-Type: application/json'];
        $this->curl = new WebCurl($headers);
	}
	
	public function memdetail(Request $req) {
			
			$notif	= $this->_checkToken($req->json('token'));
			if( $req->input('phone') ) {
				$phone = $req->json('phone');
        	 }	else {
            	$notif->setDataError();
            }

			if($notif->isOK()) {

                $sqlci = "Select idcard_file from tb_member_idcard where phone ='".$phone."'";
                $ckid = DB::select($sqlci);
                $cekid = count($ckid);

                if ($cekid == 0 ){
                    $img_path=env('IMG_ID_URL')."notfound.png";
                }  else {
                    $img_path=env('IMG_ID_URL').$phone."_id.jpg";
                }

				$sql = "SELECT member_name, phone, email, address, register_date, locker_name as default_locker_name, 		latitude, longitude, 
                         CONCAT('".$img_path."') AS id_card_image,
                         id_card_validate_status 
                        from tb_member 
						where phone='".$phone."'";
       			$resp = DB::select($sql);

       			$ttl = count($resp);
       			$total = ceil($ttl/10);

       			$notif->setOK($total,$resp);
				
			}
		
		
		return response()->json($notif->build());
	}
	
	public function memupdate(Request $req) {
			
			$notif	= $this->_checkToken($req->json('token'));
			if( $req->input('member_name') &&  $req->input('email') && $req->input('phone') ) {
				$phone = $req->json('phone');
				$email = $req->json('email');
				$address = $req->json('address');
				$member_name = $req->json('member_name');
				$locker_name = $req->json('default_locker_name');
				$latitude = $req->json('latitude');
				$longitude = $req->json('longitude');
        	 }	else {
            	$notif->setDataError();
                return response()->json($notif->build());
         	}

			if($notif->isOK()) {

				  DB::table('tb_member')
                    ->where('phone', $phone)
                    ->update(array(
                    'email' => $email,
                    'address' => $address,
                    'member_name' => $member_name,
                    'locker_name' => $locker_name,
                    'latitude' => $latitude,
                    'longitude' => $longitude
                    ));   
				$total=1;
       			$resp=['update_data' => 'ok'];
				$notif->setOK($total,$resp);
			}
		    
        $resp=['response' => ['code' => '200','message' =>"member update data success"], 'data'=>[]];
        return response()->json($resp);
		
		
	}


	public function memregister(Request $req) {
		$notif	= $this->_checkToken($req->json('token'));

		if( $req->input('phone') ) {
			$phone = $req->json('phone');
        }	else {
            $notif->setDataError();
            return response()->json($notif->build());
        }

        if($notif->isOK()) {

        	$sqlcektlp = "select count(phone) as cekphone from tb_member_token where phone='".$phone."'";
        	$cktlp = DB::select($sqlcektlp);
        	$ck = $cktlp[0]->cekphone;

			$pin = rand (1, 9999);
			$member_token=str_pad($pin,4,"0",STR_PAD_LEFT);
			$token_valid_date = date('Y-m-d H:i:s', strtotime("+5 min"));

        	if ($ck == 0 ){
				$ins = array(
            	'phone' => $phone,
            	'member_token' => $member_token,
            	'token_valid_date' =>  $token_valid_date
            	);
				DB::table('tb_member_token')->insert($ins);
        	} else {
        		DB::table('tb_member_token')
                    ->where('phone', $phone)
                    ->update(array(
                    'member_token' => $member_token,
                    'token_valid_date' =>  $token_valid_date
                    ));   
        	}


        	//send sms to user
        	$message = "Kode Verifikasi Popbox anda adalah ".$member_token.", berlaku sampai ".$token_valid_date." WIB";


       // 	send sms using jatis
        
        $sms = json_encode(['to' => $phone,
                'message' => $message,
                'token' => '0weWRasJL234wdf1URfwWxxXse304'
                ]);

        	$urlsms = "http://smsdev.clientname.id/sms/send";
        

         // send sms using twillio   
        /*    $phone = substr($phone, 1);
            $phone = "+62".$phone;

             $sms = json_encode(['to' => $phone,
                'message' => $message,
                'token' => '2349oJhHJ20394j2LKJO034823423'
                ]);

            $urlsms = "http://smsdev.clientname.id/sms/send/tw";
        */
       		$resp = json_decode($this->curl->post($urlsms, $sms), true);

            // insert partner response to database
            DB::table('companies_response')
                    ->insert([
                            'api_url' => $urlsms,
                            'api_send_data' => $sms,
                            'api_response'  => json_encode($resp), 
                            'response_date'     => date("Y-m-d H:i:s")
                ]);

        }
        $resp2=['response' => ['code' => '200','message' =>"pin sent"],'data'=>[]]; 

		return response()->json($resp2);	

	}


	public function memvalidation(Request $req) {
		$notif	= $this->_checkToken($req->json('token'));

			if( $req->input('phone') && $req->input('pin') && $req->input('gcm_token_id')  ) {
				$phone = $req->json('phone');
				$pin = $req->json('pin');
                $gcm_token_id = $req->json('gcm_token_id');
        	} else {
            	$notif->setDataError();
                return response()->json($notif->build());
        	}

        	if($notif->isOK()) {
        		//cek pin match with phone
        		$sqlpin = "select * from tb_member_token where phone='".$phone."' 
        					and member_token = '".$pin."'";
        		$cpin = DB::select($sqlpin);
        		$cekpin = count($cpin);

        		if ($cekpin > 0) {
        			$token_valid_date=$cpin[0]->token_valid_date;
        			$submit_time = date('Y-m-d H:i:s');

        			if ($submit_time < $token_valid_date) {
        				
        				//cek if member exist
        				$sqlmem = "Select count(phone) as cekphone from tb_member where phone='".$phone."'";
        				$cm = DB::select($sqlmem);
        				$cekm = $cm[0]->cekphone;

        				if ($cekm == 0){	
        					// insert database member
        				 	 DB::table('tb_member')
                    				->insert([
                            		'phone' => $phone,
                                    'member_gcm_token' => $gcm_token_id,
                            		'register_date' => date('Y-m-d H:i:s')
              			  		]);
                    	}	else {
                            //update database member
                              DB::table('tb_member')
                                    ->where('phone', $phone)
                                    ->update(array(
                                    'member_gcm_token' => $gcm_token_id
                                 ));   
                        }
						$resp=['response' => ['code' => '200','message' =>"pin validation success"], 'data' => [['session_id'=> time()]]]; 
        				
        			} else {
        			    $resp=['response' => ['code' => '402','message' =>"pin expired, please request pin again"], 'data' => [['session_id'=> time()]]]; 
        			}

        		} else {
        			$resp=['response' => ['code' => '403','message' =>"invalid pin"], 'data' => [['session_id'=> time()]]]; 
        	  	}			

        	}
            return response()->json($resp);		
	}	


    public function memuploadid(Request $req) {
            $notif  = $this->_checkToken($req->json('token'));
            if( $req->input('phone') && $req->input('idfile')  ) {
                $phone = $req->json('phone');
                $idfile = $req->json('idfile');
             }  else {
                $notif->setDataError();
                return response()->json($notif->build());
            }

            if($notif->isOK()) {
                $output_file = env('IMG_PATH')."member/".$phone."_id.jpg";
                $ifp = fopen($output_file, "wb"); 
                fwrite($ifp, base64_decode($idfile)); 
                fclose($ifp); 

                //cek if image exist in database
                $sqlck = "SELECT count(*) as cekimage FROM tb_member_idcard where phone='".$phone."'";
                $cki = DB::select($sqlck);
                $ceki = $cki[0]->cekimage;

                if ($ceki == 0) {
                        DB::table('tb_member_idcard')
                            ->insert([
                                'phone' => $phone,
                                'idcard_file' => $output_file,
                                'idcard_validation_status'  => 'No', 
                                'idcard_submit_date'     => date("Y-m-d H:i:s")
                        ]);
                } else {
                        DB::table('tb_member_idcard')
                            ->where('phone', $phone)
                            ->update(array(
                            'idcard_file' => $output_file,
                            'idcard_validation_status'  => 'No', 
                            'idcard_submit_date'     => date("Y-m-d H:i:s"),
                            'idcard_approve_date'     =>'NULL'
                        ));   
                }   
              $resp=['response' => ['code' => '200','message' =>"upload id card success"], 'data' => []];
            }
        
        return response()->json($resp);
    }

    public function memcreatepwd(Request $req) {
        $notif  = $this->_checkToken($req->json('token'));
            
            if( $req->json('phone') ) {
                $phone = $req->json('phone');
            }  else {
                $notif->setDataError();
                return response()->json($notif->build());
            }

            if($notif->isOK()) {
                
                //cek if member exist
                $sqlmem = "Select count(phone) as cekphone from tb_member where phone='".$phone."'";
                $cm = DB::select($sqlmem);
                $cekm = $cm[0]->cekphone;

                if ($cekm == 0){ 
                     $resp=['response' => ['code' => '300','message' =>"user $phone doesn't exist"], 'data' => []];
                    return response()->json($resp);
                } else {   
                    $charac = 'abcdefghijklmnopqrstuvwxyz0123456789';
                    $mem_pwd = '';
                    $max = strlen($charac) - 1;
                    for ($i = 0; $i < 6; $i++) {
                        $mem_pwd .= $charac[mt_rand(0, $max)];
                    }
                    $member_password = hash("sha256",$mem_pwd);

                    DB::table('tb_member')
                         ->where('phone', $phone)
                         ->update(array(
                            'member_password' => $member_password
                            ));   

                    //send sms to user
                    $message = "Password Popsend Anda adalah ".$mem_pwd;

                    //   send sms using jatis
                    $sms = json_encode(['to' => $phone,
                        'message' => $message,
                        'token' => '0weWRasJL234wdf1URfwWxxXse304'
                        ]);

                    $urlsms = "http://smsdev.clientname.id/sms/send";
                    $resp = json_decode($this->curl->post($urlsms, $sms), true);

                      // insert partner response to database
                        DB::table('companies_response')
                        ->insert([
                            'api_url' => $urlsms,
                            'api_send_data' => $sms,
                            'api_response'  => json_encode($resp), 
                            'response_date'     => date("Y-m-d H:i:s")
                        ]);

                    $resp=['response' => ['code' => '200','message' =>"create password for $phone success"], 'data' => $mem_pwd ];
                    return response()->json($resp);
                }
            } 
    }    

    public function memlogin(Request $req) {        
         $notif  = $this->_checkToken($req->json('token'));
         
           if( $req->json('phone') && $req->json('password')  ) {
                $phone = $req->json('phone');
                $password = $req->json('password');
            }  else {
                $notif->setDataError();
                return response()->json($notif->build());
            }
            


            if($notif->isOK()) {
                // cek if user exist
                $sqlmem = "Select count(phone) as cekphone from tb_member where phone='".$phone."'";                
                $cm = DB::select($sqlmem);
                $cekm = $cm[0]->cekphone;

                if ($cekm == 0){ 
                    $resp=['response' => ['code' => '300','message' =>"user $phone doesn't exist"]];
                    return response()->json($resp);
                } else {   
                    // cek password 
                    $member_password = hash("sha256",$password);
                    $sqlpwd = "select count(member_password) as cekpwd, id_member, phone, email from tb_member where phone='".$phone."' and
                                member_password ='".$member_password."'";

                    $member = DB::select($sqlpwd);                    
                    $cekpwd = $member[0]->cekpwd;

                    if ($cekpwd == 0) {
                        $resp=['response' => ['code' => '301','message' =>"wrong password",'result'=>Array()]];
                        return response()->json($resp);
                    }  else {                                                
                        $token = Users::updateToken($member);
                        $resp=['response' => ['code' => '200','message' =>"login success", 'result'=>Array('uid_token'=>$token)]];
                        return response()->json($resp);
                    }         
                } 
            }  

    }    
    
    public function memupdatepwd(Request $req) {
           $notif  = $this->_checkToken($req->json('token'));

            if( $req->json('phone') && $req->json('password')  ) {
                $phone = $req->json('phone');
                $password = $req->json('password');
            }  else {
                $notif->setDataError();
                return response()->json($notif->build());
            }

            if($notif->isOK()) {
                $member_password = hash("sha256",$password);
                DB::table('tb_member')
                    ->where('phone', $phone)
                    ->update(array(
                    'member_password' => $member_password
                    ));   
            
                $resp=['response' => ['code' => '200','message' =>"password for $phone has been changed"],'data'=>[]];
                return response()->json($resp);

            } 
    }    

    public function memnewsletter(Request $req) {
            $notif  = $this->_checkToken($req->json('token'));

            if( $req->json('email') && $req->json("ip_address") ) {
                $email = $req->json('email');
                $ip_address = $req->json('ip_address');
            }  else {
                $notif->setDataError();
                return response()->json($notif->build());
            }

            if($notif->isOK()) {
                 // cek if user exist
                $sqlmem = "Select count(email) as cekemail from tb_popsend_mailmember where email='".$email."'";                
                $cm = DB::select($sqlmem);
                $cekm = $cm[0]->cekemail;

                if ($cekm == 0){ 
                         DB::table('tb_popsend_mailmember')
                            ->insert([
                            'email' => $email,
                            'ip_address' => $ip_address,
                            'update_time' => date("Y-m-d H:i:s")
                        ]);
                }    


                $resp=['response' => ['code' => '200','message' =>"Thank you for registering our newsletter"],'data'=>[]];
                return response()->json($resp);

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
