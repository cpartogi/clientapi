<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;

use App\LockerActivity;
use App\LockerActivitiePickUp;
use App\CompanyNotification;

use App\Http\Helpers\CompanyLogNotification;
use App\Http\Helpers\CompanyLogResponse;

class TapToPickPush extends Command
{
	const COMPANY_ID = 28;
	
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'taptopick';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'push order to taptopick';

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
//        $this->comment(PHP_EOL.'TapToPick Push order data'.PHP_EOL);
		
		$notifications = CompanyNotification::select(['id_locker_activities'])
							->where('id_locker_activities', 'LIKE', 'TAP%')
							->get();
		
		$activities = LockerActivity::where('barcode', 'LIKE', 'TAP%')
						->where('status', 'IN_STORE')
						->whereNotIn('barcode', $notifications)
						->get();
		if($activities != null) {
			$count = count($activities);
			$idx = 0;
			foreach($activities as $activity) {
				$idx++;
				$last = $idx == $count;
				echo PHP_EOL.'activity: '.$activity->barcode.($last?' - last row':'').PHP_EOL;
				
				(new CompanyLogNotification(TapToPickPush::COMPANY_ID, $activity->barcode, $activity->status));//.save();
			}
		}
//		curl activities
		
//		curl pickup
		$pickUps = LockerActivitiePickUp::where('tracking_no', 'LIKE', 'TAP%')
						->where(function ($q) {
							$q->where('status', 'IN_STORE')
								->orWhere('status', 'COURIER_TAKEN');
						})
						->whereNotIn('tracking_no', $notifications)
						->get();
		if($pickUps != null) {
			$count = count($pickUps);
			$idx = 0;
			foreach($pickUps as $pickup) {
				$idx++;
				$last = $idx == $count;
				echo PHP_EOL.'pickup: '.$pickup->tracking_no.($last?' - last row':'').PHP_EOL;
				
				(new CompanyLogNotification(TapToPickPush::COMPANY_ID, $pickup->tracking_no, $pickup->status));//.save();
			}
		}
    }
}
