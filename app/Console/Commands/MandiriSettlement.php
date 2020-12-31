<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\BankSettlement;

class MandiriSettlement extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mandirisettlement';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create settlement txt file for Mandiri';

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
    public function handle()
    {
		echo PHP_EOL.'mandiri settlement'.PHP_EOL;
		
		$gap = -12;//hour
		$etime = time();
		$stime = strtotime($gap.' hours', $etime);
		
        $endDate = date('Y-m-d H:i:s', $etime);
		$startDate = date("Y-m-d H:i:s", $stime);
		
		$oprId = '0001';
		$shiftId = '0001';
		$seqId = '01';
		$createdDate = date('dmYHi');
		$submitDate = date('dmYHi');
		$signature = '4514';
		
		$terminals = BankSettlement::select('terminal_id')
						->where('settle_timestamp', '>=', $startDate)
						->where('settle_timestamp', '<=', $endDate)
						->groupBy('terminal_id')
						->get();
		
		if($terminals != null) {
			foreach($terminals as $terminal) {
				$terminalId = $terminal->terminal_id;
				
				if($terminalId == '') continue;
				
				echo PHP_EOL.'terminal id: '.$terminalId.PHP_EOL;
				
				$fileName = $oprId.$shiftId.$terminalId.$seqId.$createdDate.$submitDate.$signature.'.txt';

				$sumSettle = BankSettlement::where('settle_timestamp', '>=', $startDate)
									->where('settle_timestamp', '<=', $endDate)
									->where('terminal_id', $terminalId)
									->sum('order_amount');

				$settlements = BankSettlement::where('settle_timestamp', '>=', $startDate)
									->where('settle_timestamp', '<=', $endDate)
									->where('terminal_id', $terminalId)
									->get();

				$header = 'PREPAID';
				$header .= str_pad((count($settlements) + 2), 8, '0', STR_PAD_LEFT);
				$header .= str_pad($sumSettle, 12, '0', STR_PAD_LEFT);
				$header .= $shiftId;
				$header .= $oprId;
				$header .= date('dmY');
				$header .= chr(3)."\n";

				$trailer = $oprId;
				$trailer .= str_pad(count($settlements), 8, '0', STR_PAD_LEFT);

				if($settlements != null) {			
					$content = $header;

					foreach($settlements as $settlement) {
						$content .= $settlement->settle_code.chr(3)."\n";
					}

					$content .= $trailer.chr(3);			
				}

				Storage::put('mandiri_settlement/'.$fileName, $content);

			/*	$connection = ssh2_connect('103.28.14.188', 22222);
				ssh2_auth_password($connection, 'popbox', 'P0pb0x');

				ssh2_scp_send($connection, 'mandiri_settlement/'.$fileName , '/home/popbox/settlement/'.$fileName, 0644);*/


				$content2 = "";
				$filename2 = $oprId.$shiftId.$terminalId.$seqId.$createdDate.$submitDate.$signature.'.OK';
				Storage::put('mandiri_settlement/'.$filename2, $content2);

			/*	ssh2_scp_send($connection, 'mandiri_settlement/'.$filename2 , '/home/popbox/settlement/'.$filename2, 0644);*/
			}
		}
    }
}
