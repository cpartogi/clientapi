<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;

use App\LockerActivity;
use App\LockerActivitiePickUp;
use App\CompanyNotification;

use App\Http\Helpers\CompanyLogNotification;
use App\Http\Helpers\CompanyLogResponse;

class PromoDatsun extends Command
{
	
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'promodatsun';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'process datsun book donation';

    /**
     * Create a new command instance.
     *
     * @return void
     */
//    public function __construct()
//    {
//        parent::__construct();
//    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
    //ambil data terakhir untuk aktivitas datsun store parcel to locker
	$sql= "SELECT id, tracking_no, phone_number, locker_name  FROM locker_activities_pickup where status='IN_STORE' and tracking_no like 'DRH%' order by storetime desc limit 0,10";
		$res= DB::select($sql);
		$jml = count($res);

		if ($jml > 0 ) {
   			foreach ($res as $rs) {
				//cek apakah notifikasi sudah dikirim ke customer
				$sqlcn = "select id_notification from companies_notification where id_locker_activities = '".$rs->id."' and notification_status='IN_STORE'";
				$rn	= count(DB::select($sqlcn));

				if ($rn == 0 ){

					$urlsms = "http://smsdev.clientname.id/sms/send";

					// kirim sms ke pelanggan 
					$messageuser = "Terima kasih sudah berpartisipasi dalam DATSUN RISING HOPE, kami akan mengirimkan buku Anda ke Yayasan 1001Buku.";

					$sms = json_encode(['to' => $rs->phone_number,
               				 'message' => $messageuser,
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

                    //insert to table notification
			   		DB::table('companies_notification')
						->insert([
						'id_company' => '7',
						'id_locker_activities' => $rs->id,
						'notification_status' => 'IN_STORE',
						'notification_date' =>  date("Y-m-d H:i:s")
						]); 		

					//kirim sms ke admin popbox
  					$sqlsmsa = "select admin_phone from admin_notification";
					$admins = DB::select($sqlsmsa);
					$messageadmin = "Mohon lakukan pengambilan donasi Datsun ke locker ".$rs->locker_name.". untuk kode parsel ".$rs->tracking_no ;

					
					foreach ($admins as $admin ) {
							$sms = json_encode(['to' => $admin->admin_phone,
               				 'message' => $messageadmin,
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
				}	
			}

		}
			
    }
}
