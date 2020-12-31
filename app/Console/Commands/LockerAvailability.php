<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Helpers\WebCurl;

class LockerAvailability extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lockeravailability';
	var $curl;
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'create locker availability report';

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
	/*	$this->data['locker_capacity'] =  DB::select('
									SELECT a.name, a.locker_id, a.locker_capacity, b.district, c.province from locker_locations a, districts b, provinces c where a.district_id=b.id and c.id=b.province_id order by province, district, name');
		$this->data['locker_capacity_max'] = DB::table('locker_locations')->max('locker_capacity');
		$this->data['locker_capacity_total'] = DB::table('locker_locations')->sum('locker_capacity');
		$this->data['locker_total'] =  DB::table('locker_locations')->count('id'); */
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


                $sqldat = "SELECT a.name, a.locker_id, a.locker_capacity, b.district, c.province, d.building_type from locker_locations a, districts b, provinces c, buildingtypes d where a.district_id=b.id and c.id=b.province_id and a.building_type_id=d.id_building and a.locker_id='".$id."'";
                $ldata = DB::select($sqldat);
                $capacity = $ldata[0]->locker_capacity;
                $building_type = $ldata[0]->building_type;
                $availability = $locker['availability'] ;
                $occupied = round((($capacity-$availability)/$capacity)*100);

                // cek if data exist
                $sqlcek = "select locker_name from tb_locker_availability_report where locker_name='".$locker['name']."'";
                $cek = count(DB::select($sqlcek));

                if ($cek == 0 ) {
                    DB::table('tb_locker_availability_report')
                            ->insert([
                                    'locker_name' => $locker['name'],
                                    'city' => $locker['district'],
                                    'province'  => $locker['province'] , 
                                    'building_type'  => $building_type,
                                    'availability' => $availability,
                                    'capacity' =>  $capacity,
                                    'occupied' => $occupied,
                                    'status' => $locker['status'],
                                    'last_update' => date("Y-m-d H:i:s")

                            ]);
                } else {
                        DB::table('tb_locker_availability_report')
                                    ->where('locker_name', $locker['name'])
                                    ->update(array(
                                         'city' => $locker['district'],
                                         'province'  => $locker['province'] , 
                                         'building_type'  => $building_type,
                                         'availability' => $availability,
                                         'capacity' =>  $capacity,
                                         'occupied' => $occupied,
                                         'status' => $locker['status'] ,
                                         'last_update' => date("Y-m-d H:i:s")
                                     ));
                }            

                echo $locker['name']."\n";
  			}
	
	

		}
		
		
		
		
    }
}
