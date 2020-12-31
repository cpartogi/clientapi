<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


class ParcelData extends Command
{
    var $curl;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parceldata';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'get parcel data';

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

        $urlget = "http://eboxapi.clientname.id:8080/ebox/api/v1/express/query?expressType=COURIER_STORE&maxCount=10&page=0";
       	$this->headers[]="userToken:".$api_token;

       	$varupload="";
       	$locker_pickup=$this->post_data($urlget, $varupload, $this->headers);

       
       
       	
       	for($i = 0; $i < count ( $locker_pickup ["resultList"] ); $i ++) {
			$locker_activity = $locker_pickup ["resultList"] [$i];

			$id = $locker_activity["id"];
			$name = $locker_activity["storeUser"]["name"]; 
			$barcode = $locker_activity["expressNumber"];
			$phone = $locker_activity["takeUserPhoneNumber"];
			$locker_name = $locker_activity ["mouth"] ["box"] ["name"];
			$locker_number = $locker_activity ["mouth"] ["number"];
			$locker_size = $locker_activity ["mouth"] ["mouthType"] ["name"];
			$storetime =  date('Y-m-d H:i:s', $locker_activity["storeTime"]/1000);
			$overduetime = date('Y-m-d H:i:s', $locker_activity["overdueTime"]/1000);
			$status = $locker_activity["status"];
			$validatecode =  $locker_activity["validateCode"];

			if ($status == "CUSTOMER_TAKEN" || $status == "COURIER_TAKEN" || $status == "OPERATOR_TAKEN") {
				$taketime =  date('Y-m-d H:i:s', $locker_activity["takeTime"]/1000);
			} else {
				$taketime = "";
			}
		
			// cek already exist 
			$sql= "SELECT id FROM locker_activities where id='".$id."'";
			$res= DB::select($sql);
			$rest	= count($res);

			if($rest != 0)
			{
				echo "already exist : ";
				
					// cek if status changed
					$sqls= "SELECT id, status FROM locker_activities where id='".$id."' and status='".$status ."'";
					$ress= DB::select($sqls);
					$rs	= count($ress);	

					if ($rs == 0 ) {
						DB::table('locker_activities')
                   			->where('id', $id)
                    		->update(array(
                  				'name'  => $name,
								'barcode' => $barcode,
								'phone' => $phone,
								'locker_name' => $locker_name,
								'locker_number' => $locker_number,
								'locker_size' => $locker_size,
								'storetime' => $storetime,
								'taketime' => $taketime,
								'status' => $status,
								'validatecode' => $validatecode,
								'overduetime' => $overduetime
                    	)); 
                    	echo "status changed \n";	     
					} else {
						echo "no status changed \n";
					}

			} else {	
			   DB::table('locker_activities')
					->insert([
					'id'    => $id,
					'name'  => $name,
					'barcode' => $barcode,
					'phone' => $phone,
					'locker_name' => $locker_name,
					'locker_number' => $locker_number,
					'locker_size' => $locker_size,
					'storetime' => $storetime,
					'taketime' => $taketime,
					'status' => $status,
					'validatecode' => $validatecode,
					'overduetime' => $overduetime
				]); 
					
			}
			echo $id. " , ";
      	} 
    }
}
