<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        /*Commands\Inspire::class,*/
		Commands\TapToPickPush::class,
		Commands\MandiriSettlement::class,
        Commands\PickUpRequest::class,
        Commands\ExportParcel::class,
        Commands\ParcelReturn::class,
        Commands\ParcelData::class,
        Commands\LockerNotification::class,
        Commands\LockerAvailability::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
//         $schedule->command('inspire')->everyMinute()->sendOutputTo('/Users/viskaya/Desktop/inspire-output.txt');
//		$schedule->command('taptopick')->everyMinute()->sendOutputTo('/Users/viskaya/Desktop/taptopick-output.txt');
//		$schedule->command('mandirisettlement')->everyMinute()->sendOutputTo('/Users/viskaya/Desktop/mandiri-output.txt');//twiceDaily(10, 22);
        $schedule->command('parceldata')->everyMinute()->sendOutputTo('/home/developer/apiv2/storage/app/debugapp/parceldata.txt');
        $schedule->command('parcelreturn')->everyMinute()->sendOutputTo('/home/developer/apiv2/storage/app/debugapp/parcelreturn.txt');
        $schedule->command('mandirisettlement')->twiceDaily(10, 22);
        $schedule->command('pickuprequest')->everyMinute()->sendOutputTo('/home/developer/apiv2/storage/app/debugapp/pickuprequest.txt');
        $schedule->command('exportparcel')->everyMinute()->sendOutputTo('/home/developer/apiv2/storage/app/debugapp/exportparcel.txt');
        $schedule->command('lockernotification')->twiceDaily(9, 21)->sendOutputTo('/home/developer/apiv2/storage/app/debugapp/offlinelocker.txt');
        $schedule->command('lockeravailability')->everyTenMinutes()->sendOutputTo('/home/developer/apiv2/storage/app/debugapp/lockeravailability.txt');


           
    }
}
