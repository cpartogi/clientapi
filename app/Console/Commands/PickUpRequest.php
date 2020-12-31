<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;
use App\Http\Helpers\WebCurl;
use Illuminate\Http\Request;

class PickUpRequest extends Command
{

	var $curl;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pickuprequest';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create schedule task for pickup request';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */


    // function from old app 
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


    public function handle()
    {
    	
		$username = 'OPERATOR4API';
        $password = "p0pb0x4p10p3r"; 

        $urlogin = "http://eboxapi.clientname.id:8080/ebox/api/v1/user/login";
        $varlogin = [
        			 'loginName' => $username,
        			  'password' => $password
        						];
  		$logres=$this->post_data($urlogin,json_encode ( $varlogin ), null);
        $api_token=$logres['token'];
        //echo "token : ".$api_token;

        $urlget = "http://eboxapi.clientname.id:8080/ebox/api/v1/express/query?expressType=CUSTOMER_STORE&maxCount=10&page=0";
       	$this->headers[]="userToken:".$api_token;

       	$varupload="";
       	$locker_pickup=$this->post_data($urlget, $varupload, $this->headers);

      
     	if ((is_array($locker_pickup)) AND (array_key_exists("totalCount", $locker_pickup)))
		{
			$total_count = $locker_pickup["totalCount"];
		}	
	
		for($i = 0; $i < count ( $locker_pickup ["resultList"] ); $i ++) {
			$locker_activity = $locker_pickup ["resultList"] [$i];

			$id = $locker_activity["id"];
			$tracking_no = $locker_activity["customerStoreNumber"];
			$phone_number = $locker_activity ["takeUserPhoneNumber"];
			$locker_name = $locker_activity ["mouth"] ["box"] ["name"];
			$locker_number = $locker_activity ["mouth"] ["number"];
			$locker_size = $locker_activity ["mouth"] ["mouthType"] ["name"];
			$storetime =  date('Y-m-d H:i:s', $locker_activity["storeTime"]/1000);
			
			$status = $locker_activity["status"];

			if ($status == 'IN_STORE') {
				$taketime = "0000-00-00 00:00:00";
			} else {
				$taketime = date('Y-m-d H:i:s', $locker_activity["takeTime"]/1000);
			}

			// cek already exist 
			$sql= "SELECT id FROM locker_activities_pickup where id='".$id."'";
			$res= DB::select($sql);
			$rest	= count($res);

			if($rest != 0)
			{
				echo "already exist : ";
					// cek if status changed
					$sqls= "SELECT id, status FROM locker_activities_pickup where id='".$id."' and status='".$status ."'";
					$ress= DB::select($sqls);
					$rs	= count($ress);

					if ($rs == 0 ) {	

						DB::table('locker_activities_pickup')
                    		->where('id', $id)
                    		->update(array(
                 				'tracking_no'	=> $tracking_no,
								'phone_number'	=> $phone_number,
								'locker_name' => $locker_name,
								'locker_number' => $locker_number,
								'locker_size' => $locker_size,
								'storetime' => $storetime,
								'taketime' => $taketime,
								'status' => $status
                    	));
                    	echo "status changed \n";	     	      
                	} else {
                		echo "no status changed \n";
                	}

			} else {	
			   DB::table('locker_activities_pickup')
					->insert([
					'id' => $id,
					'tracking_no' => $tracking_no,
					'phone_number'	=> $phone_number,
					'locker_name' => $locker_name,
					'locker_number' => $locker_number,
					'locker_size' => $locker_size,
					'storetime' => $storetime,
					'taketime' => $taketime,
					'status' => $status
				]); 	
			}
			echo $id. " , ";

			$track_no = substr($tracking_no, 0, 3);

			if ($status == 'IN_STORE' && $track_no == 'PLA') {
				//cek apakah notifikasi sudah dikirim
				$sqlcn = "select id_notification from companies_notification where id_locker_activities = '".$id."' and notification_status='IN_STORE'";
				$rn	= count(DB::select($sqlcn));

				if ($rn == 0 ){
					//kirim sms ke pengirim
					$sqlsms = "select phone from tb_member_pickup where invoice_id='".$tracking_no."'";
					$sender = DB::select($sqlsms);
					$senderno = $sender[0]->phone;
					$messagesender = "Terima kasih telah mengirim barang Anda dengan order no:".$tracking_no." via Popbox. Kurir akan segera mengantarkan paket Anda ke tempat tujuan.";
				
					$urlsms = "http://smsdev.clientname.id/sms/send";

					 $sms = json_encode(['to' => $senderno,
               				 'message' => $messagesender,
                			'token' => '72472b3AW3WEG3249i239565Ddfg5'
              			  ]);

					
				
 					$resp = $this->post_data($urlsms, $sms);
				  

					DB::table('companies_response')
                    ->insert([
                            'api_url' => $urlsms,
                            'api_send_data' => $sms,
                            'api_response'  => json_encode($resp), 
                            'response_date'     => date("Y-m-d H:i:s")
               		]);
				
                    // kirim push notification ke apps
                   	//get member gcm
					$sqlgcm = "select member_gcm_token from tb_member where phone='".$senderno."'";
					//echo "\n".$sqlgcm."\n";
					$rsgcm = DB::select($sqlgcm);
					$gcm = $rsgcm[0]->member_gcm_token; 
					//echo "gcm = ".$gcm."\n";


                   $urlupload = "https://gcm-http.googleapis.com/gcm/send";
                    
       				$this->headers[]="Authorization:key=AIzaSyAvS7RjxCoP3RbQK6bKbHJbaIzuAcjIpU4";
       				//echo "\n tracking_no : ".$tracking_no."\n";

       				$varupload = [
           					'data' => ['service'=>'pickup request', 'detail'=> ['invoice_id'=> $tracking_no], 'title'=>'Terima kasih telah mengirim barang'],
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
					
				



					//kirim sms ke admin popbox
  					$sqlsmsa = "select admin_phone from admin_notification";
					$admins = DB::select($sqlsmsa);
					$messageadmin = "Ada pick up request baru dengan no invoice ".$tracking_no." silakan diproses melalui dashboard" ;

					foreach ($admins as $admin ) {
							$sms = json_encode(['to' => $admin->admin_phone,
               				 'message' => $messageadmin,
                			'token' => '72472b3AW3WEG3249i239565Ddfg5'
              			  ]);
						  
						  $resp = $this->post_data($urlsms, $sms);
						  

						DB::table('companies_response')
                    	->insert([
                            'api_url' => $urlsms,
                            'api_send_data' => $sms,
                            'api_response'  => json_encode($resp), 
                            'response_date'     => date("Y-m-d H:i:s")
               		]);

					} 

			   		//insert to table notification
			   		DB::table('companies_notification')
						->insert([
						'id_company' => '7',
						'id_locker_activities' => $id,
						'notification_status' => $status,
						'notification_date' =>  date("Y-m-d H:i:s")
						]); 				
				
				}
			}	
		


		}


		//ambil data terakhir untuk locker to locker dan locker to address
		$sql= "SELECT id, tracking_no, phone_number  FROM locker_activities_pickup where status='COURIER_TAKEN' and tracking_no like 'PLL%' or tracking_no like 'PLA%' order by taketime desc limit 0,20";
		$res= DB::select($sql);

		foreach ($res as $rs) {
				//cek apakah notifikasi sudah dikirim
				$sqlcn = "select id_notification from companies_notification where id_locker_activities = '".$rs->id."' and notification_status='COURIER_TAKEN'";
				$rn	= count(DB::select($sqlcn));

				if ($rn == 0 ){

					// cek if locker to address not send data to pakpopbox
        			$prefix = substr($rs->tracking_no, 0, 3);
        			
        			$urlsms = "http://smsdev.clientname.id/sms/send";
					//kirim sms ke admin popbox
  					$sqlsmsa = "select phone from tb_member_pickup where invoice_id='".$rs->tracking_no."'";
					$admins = DB::select($sqlsmsa);
					$messageuser = "Order Anda ".$rs->tracking_no." sudah diambil oleh kurir kami dan segera akan dikirimkan. Lacak paket Anda: https://popsend.clientname.id/tracking";
					$messageadmin = "Mohon lakukan pengiriman pick up request ke locker untuk no invoice ".$rs->tracking_no." detail alamat bisa dilihat di dashboard" ;

					
				//	foreach ($admins as $admin ) {
							$sms = json_encode(['to' => $admins[0]->phone,
               				 'message' => $messageuser,
                			'token' => '72472b3AW3WEG3249i239565Ddfg5'
              			  ]);
						//  $resp = json_decode($this->curl->post($urlsms, $sms), true);
						  $resp = $this->post_data($urlsms, $sms);

						DB::table('companies_response')
                    	->insert([
                            'api_url' => $urlsms,
                            'api_send_data' => $sms,
                            'api_response'  => json_encode($resp), 
                            'response_date'     => date("Y-m-d H:i:s")
               		]);

				//	} 
				

					// kirim push notif ke apps pengirim, barang sudah diambil kurir
					//get member gcm
					$sqlgcm = "select a.member_gcm_token from tb_member a, tb_member_pickup b
							   where a.phone=b.phone and b.invoice_id='".$rs->tracking_no."'";
					//echo "\n".$sqlgcm."\n";
					$rsgcm = DB::select($sqlgcm);
					$gcm = $rsgcm[0]->member_gcm_token; 
					//echo "gcm = ".$gcm."\n";


                   $urlupload = "https://gcm-http.googleapis.com/gcm/send";
                    
       				$this->headers[]="Authorization:key=AIzaSyAvS7RjxCoP3RbQK6bKbHJbaIzuAcjIpU4";
       				//echo "\n tracking_no : ".$tracking_no."\n";

       				$varupload = [
           					'data' => ['service'=>'pickup request', 'detail'=> ['invoice_id'=> $rs->tracking_no], 'title'=>'Barang telah diambil oleh kurir'],
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

			   		//insert to table notification
			   		DB::table('companies_notification')
						->insert([
						'id_company' => '7',
						'id_locker_activities' => $rs->id,
						'notification_status' => 'COURIER_TAKEN',
						'notification_date' =>  date("Y-m-d H:i:s")
						]); 	
					
				
				}	

		}

		// ambil data terakhir untuk address to locker
		$sqlal = "select phone, invoice_id from tb_member_pickup where invoice_id like 'PAL%' order by pickup_order_date desc limit 0,1";
		$resal = DB::select($sqlal);
		$cnt = count($resal);

		if ($cnt != 0) {
			//cek apakah notifikasi sudah dikirim
			$sqlcek = "Select id_locker_activities from companies_notification where id_locker_activities='".$resal[0]->invoice_id."'";
			$rscek = count(DB::select($sqlcek));

			if ($rscek == 0) {


				$urlsms = "http://smsdev.clientname.id/sms/send";
				//kirim sms ke admin popbox
  				$sqlsmsa = "select admin_phone from admin_notification";
				$admins = DB::select($sqlsmsa);
				$messageadmin = "Ada pick up request baru dengan no invoice ".$resal[0]->invoice_id." silakan diproses melalui dashboard" ;

				foreach ($admins as $admin ) {
					$sms = json_encode(['to' => $admin->admin_phone,
               				 'message' => $messageadmin,
                			 'token' => '72472b3AW3WEG3249i239565Ddfg5'
              			    ]);
					
					$resp = $this->post_data($urlsms, $sms);

				}

			//insert to table notification
			   		DB::table('companies_notification')
						->insert([
						'id_company' => '7',
						'id_locker_activities' => $resal[0]->invoice_id,
						'notification_status' => 'NEED_STORE',
						'notification_date' =>  date("Y-m-d H:i:s")
						]); 	
			}		
		}	


		// kirim notifikasi ke sender jika barang sudah diterima oleh pelanggan
		$sqlsdr = "select id, barcode, taketime from locker_activities where barcode like 'PLL%' or barcode like 'PLA%' or barcode like 'PAL%' order by taketime desc limit 0,10";
		$resdr = DB::select($sqlsdr);
		$cnt = count($resdr);

		if ($cnt != 0) {
			//cek apakah notifikasi sudah dikirim
			$sqlcek = "Select id_locker_activities from companies_notification where id_locker_activities='".$resdr[0]->id."' and notification_status='CUSTOMER_TAKEN'";
			$rscek = count(DB::select($sqlcek));

			if ($rscek == 0) {


				$urlsms = "http://smsdev.clientname.id/sms/send";
				//kirim sms ke sender
  				$sqlsmsa = "select phone from tb_member_pickup where invoice_id='".$resdr[0]->barcode."'";
				$senders = DB::select($sqlsmsa);
				$date = date_create($resdr[0]->taketime);
				$taketime = date_format($date, 'd/m/y');

				$messagesender = "Barang Anda dengan kode ".$resdr[0]->barcode." telah diambil oleh penerima pada tanggal ".$taketime.". Terima kasih telah menggunakan Popbox." ;

					$sms = json_encode(['to' => $senders[0]->phone,
               				 'message' => $messagesender,
                			 'token' => '72472b3AW3WEG3249i239565Ddfg5'
              			    ]);
					
					$resp = $this->post_data($urlsms, $sms);
				

			         // kirim push notif ke apps pengirim, barang sudah diambil pelanggan
					//get member gcm
					$sqlgcm = "select a.member_gcm_token from tb_member a, tb_member_pickup b
							   where a.phone=b.phone and b.invoice_id='".$resdr[0]->barcode."'";
					//echo "\n".$sqlgcm."\n";
					$rsgcm = DB::select($sqlgcm);
					$gcm = $rsgcm[0]->member_gcm_token; 
					//echo "gcm = ".$gcm."\n";


                   $urlupload = "https://gcm-http.googleapis.com/gcm/send";
                    
       				$this->headers[]="Authorization:key=AIzaSyAvS7RjxCoP3RbQK6bKbHJbaIzuAcjIpU4";
       				//echo "\n tracking_no : ".$tracking_no."\n";

       				$varupload = [
           					'data' => ['service'=>'pickup request', 'detail'=> ['invoice_id'=> $rs->tracking_no], 'title'=>'Barang telah diambil oleh penerima'],
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
	

			//insert to table notification
			   		DB::table('companies_notification')
						->insert([
						'id_company' => '7',
						'id_locker_activities' => $resdr[0]->id,
						'notification_status' => 'CUSTOMER_TAKEN',
						'notification_date' =>  date("Y-m-d H:i:s")
						]); 	
			}		
		}

    }
}
