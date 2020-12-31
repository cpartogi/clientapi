<?php
/**
 * Initiate Doku data's
 */
namespace App\Http\Helpers\finclude\Core;
class DokuInitiate {
	 
	//local
	const prePaymentUrl = 'http://staging.doku.com/api/payment/PrePayment';
	const paymentUrl = 'http://staging.doku.com/api/payment/paymentMip';
	const directPaymentUrl = 'http://staging.doku.com/api/payment/PaymentMIPDirect';
	const generateCodeUrl = 'http://staging.doku.com/api/payment/doGeneratePaymentCode';
	const redirectPaymentUrl = 'http://staging.doku.com/api/payment/doInitiatePayment';
	const captureUrl = 'http://staging.doku.com/api/payment/DoCapture';

	public static $sharedKey; //doku's merchant unique key
	public static $mallId; //doku's merchant id

}


