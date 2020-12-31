<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Helpers\WebCurl;

class LockerNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lockernotification';
	var $curl;
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send notification to engineer if locker offline';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $headers    = ['Content-Type: application/json'];
        $this->curl = new WebCurl($headers);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    public function getLockers() {
		return (new WebCurl(array('token: 14d5b792dd12150bf5b8a5d6a9a7e583650d37af')))->get("http://api.clientname.id/v1/locker/location");
	}

    public function handle()
    {
		$this->data['locker_capacity'] =  DB::select('
									SELECT a.name, a.locker_id, a.locker_capacity, b.district, c.province from locker_locations a, districts b, provinces c where a.district_id=b.id and c.id=b.province_id order by province, district, name');
		$this->data['locker_capacity_max'] = DB::table('locker_locations')->max('locker_capacity');
		$this->data['locker_capacity_total'] = DB::table('locker_locations')->sum('locker_capacity');
		$this->data['locker_total'] =  DB::table('locker_locations')->count('id');
		$this->data['locker_available']	= array();
		$this->data['locker_offline'] = array();
		
		$locker_api						= json_decode($this->getLockers(), true);
//		echo '<pre>';print_r($locker_api);echo '</pre>';
		$this->data['locker_available_max'] = 0;
		
		if(isset($locker_api['result']) && is_array($locker_api['result']) && !empty($locker_api['result'])) {
		
			$lockername = '';
			foreach($locker_api['result'] as $locker) {
				$id = $locker['id'];
				$this->data['locker_available'][$id] = $locker['availability'];//$id == '2c9180884e4daccc014e4dad17c80001' ? 39 : $locker['availability'];
				if($locker['availability'] > $this->data['locker_available_max']) {
					$this->data['locker_available_max'] = $locker['availability'];
				}

                //query di sini ajah

  				if(strtolower($locker['status']) != 'enable') {
					//echo $locker['name']."\n";
					// cek already exist 
					$sql = "SELECT locker_name, locker_notif FROM locker_offline where locker_name='".$locker['name']."'";
					$res = DB::select($sql);
					$rest = count($res);

				
					if ($rest == 0 ) {	
						  DB::table('locker_offline')
							->insert([
								'locker_name' => $locker['name'],
								'locker_offline_time'	=> date("Y-m-d H:i:s"),
								'locker_notif' => '0'
							]); 	
					} else {
						// cek locker notif
						 	DB::table('locker_offline')
                    				->where('locker_name', $locker['name'])
                    				->update(array(
                 						'locker_offline_time' => date("Y-m-d H:i:s"),
										'locker_notif'	=> $res[0]->locker_notif+1
									 ));
                    		$lockername .= $locker['name'].", ";		
					}	
				}
			}
			echo "offline nih : ".$lockername;

			$message = "Offline locker : ".$lockername;

	       // send sms using jatis
           /*$sms = json_encode(['to' => '081322276873',
               					'message' => $message,
                				'token' => '0weWRasJL234wdf1URfwWxxXse304'
                			  ]);
        	$urlsms = "http://smsdev.clientname.id/sms/send";*/
        
         	
            //kirim sms ke admin popbox
            $sqlsmsa = "select admin_phone from admin_locker_notification";
            $admins = DB::select($sqlsmsa);
                
            // send sms using twillio   

            foreach ($admins as $admin ) {
         	        $phone = substr($admin->admin_phone, 1);
            	    $phone = "+62".$phone;

            	    $sms = json_encode(['to' => $phone,
                         	           'message' => $message,
                        	            'token' => '2349oJhHJ20394j2LKJO034823423'
               	                        ]);

         	        $urlsms = "http://smsdev.clientname.id/sms/send/tw";
        	
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



		}
		
		
		
		
    }
}
