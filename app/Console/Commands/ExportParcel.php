<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


class ExportParcel extends Command
{
    var $curl;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exportparcel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upload parcel data to pakpobox';

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

    	// upload multiple data to pakpobox
		$sqlp = "SELECT tracking_no, phone_number  FROM locker_activities_pickup where status='COURIER_TAKEN' and tracking_no like 'PLL%' order by taketime desc limit 0,10";
		$resps = DB::select($sqlp);


		$username = 'admin';
        $password = "761128";

        $urlogin = "http://eboxapi.clientname.id:8080/ebox/api/v1/user/login";
        $varlogin = [
            'loginName' => $username,
            'password' => $password
        	];
     
        $logres=$this->post_data($urlogin,json_encode ( $varlogin ), null);
        
        $api_token=$logres['token'];

        $urlupload = "http://eboxapi.clientname.id:8080/ebox/api/v1/express/import";
       	$this->headers[]="userToken:".$api_token;

		foreach ($resps as $resp)
		{
			echo "\n tracking_no : ".$resp->tracking_no."\n";
			echo "phone_number : ".$resp->phone_number."\n";

			
			// cek apakah ada di tabel response
			$string = '"ALREADY_EXIST":["'.$resp->tracking_no.'"]';
			$sqlck = "select api_response from companies_response where api_response like '%".$string."%'";
			$cnck = count(DB::select($sqlck));
			
			if ($cnck == 0) {


			$varupload = [
           				 'expressNumber' => $resp->tracking_no,
         				'takeUserPhoneNumber' => $resp->phone_number
    					  ];
        	$varupload = "[".json_encode($varupload)."]";			 
        			
			// post api to pakpobox        			
        	$upres=$this->post_data($urlupload, $varupload, $this->headers);
        	echo "\n".json_encode($upres);

        	// insert partner response to database
            	DB::table('companies_response')
                    	->insert([
                        'api_url' => $urlupload,
                        'api_send_data' => $varupload,
                        'api_response'  => json_encode($upres), 
                        'response_date'     => date("Y-m-d H:i:s")
                ]);	
			
			}	
		}

    }
}
