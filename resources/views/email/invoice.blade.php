<!DOCTYPE html>
<head>
    <title>PopSend Invoice</title>
    <style type="text/css">
    html,
    body {
        margin: 0;
        padding: 0;
    }
    </style>
</head>

<body>
    <script type="application/ld+json">
    {
        "@context": "http://schema.org",
        "@type": "Order",
        "merchant": {
            "@type": "Organization",
            "name": "PopSend.clientname.id"
        },
        "orderNumber": "{{$detail['invoice_id']}}",
        "orderStatus": "http://schema.org/OrderProcessing",
        "priceCurrency": "IDR",
        "price": "{{$detail['amount']}}",
        "acceptedOffer": {
            "@type": "Offer",
            "itemOffered": {
                "@type": "Product",
                "name": "PopSend",
                "url": "https://popsend.clientname.id/ord/{{$detail['invoice_id']}}"
            },
            "priceSpecification" : {
                "@type" : "PriceSpecification",
                "priceCurrency" : "IDR",
                "price" : "{{$detail['amount']}}"
            },
            "price": "{{$detail['amount']}}",
            "priceCurrency": "IDR",
            "eligibleQuantity": {
                "@type": "QuantitativeValue",
                "value": "1"
            }
        },
        "url": "https://popsend.clientname.id/ord/{{$detail['invoice_id']}}",
        "potentialAction": {
            "@type": "ViewAction",
            "url": "https://popsend.clientname.id/ord/{{$detail['invoice_id']}}",
            "target": "https://popsend.clientname.id/ord/{{$detail['invoice_id']}}"
        }
    }
    </script>
    <table align="center" border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" style="border-collapse:collapse;margin:0;padding:0;background-color:#E5EFEE; height:100%!important;width:100%!important; font-family:Roboto, Helvetica, sans-serif;">
        <tbody>
            <tr>
                <td align="center" valign="top" style="margin:0;padding:20px;border-top:0;height:100%!important;width:100%!important">
                    <table border="0" cellpadding="0" cellspacing="0" width="70%" class="flexibleContainer" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%; background:#ffffff;-webkit-text-size-adjust: 100%;">
                        <tbody>
                            <tr>
                                <td align="left" valign="top" style="margin:0;padding:20px;border-top:0;height:100%!important;width:100%!important">
                                    <div style="padding:0 50px">
                                        <div style="height:30px"></div>
                                        <a href="https://popsend.clientname.id"><img src="https://popsend.clientname.id/img/email/logo-popsendred.png"><a>
                                        <div style="height:30px"></div>
                                        <strong style="font-size:12pt">Hi {{$member_name}},</strong>
                                        <div style="height:10px"></div>
                                        <div>
                                            <p>
                                                Thank you for purchasing via <a href="https://popsend.clientname.id" style="color:#607D8D;text-decoration:none" target="_blank">popsend.clientname.id</a>, we are pleased to inform you that your payment is successful.
                                                <br>
                                                <br>Here is your details order:
                                            </p>
                                            <div style="height:25px"></div>
                                            <div style="padding:18px 20px;background: #E5EFEE; margin:0 0 40px 0">
                                                <div style="width:100%;display:table">
                                                    <div style="display:table-row">
                                                        <div style="display:table-cell;width:20px">
                                                        </div>
                                                        <div style="display:table-cell;vertical-align:top">
                                                            <div style="display:table">
                                                                <div style="display:table-row">
                                                                    <div style="display:table-cell;font-weight:bold;height:21.85px;min-width:200px">Invoice number</div>
                                                                    <div style="display:table-cell;text-align:center;width:37px">:</div>
                                                                    <div style="display:table-cell">{{$detail['invoice_id']}}</div>
                                                                </div>
                                                                <div style="display:table-row">
                                                                    <div style="display:table-cell;font-weight:bold;height:21.85px">Order Created</div>
                                                                    <div style="display:table-cell;text-align:center;width:37px">:</div>
                                                                    <div style="display:table-cell">{{date('l, d M Y H:i A', strtotime($detail['pickup_order_date']))}}</div>
                                                                </div>
                                                                <div style="display:table-row">
                                                                    <div style="display:table-cell;font-weight:bold;height:21.85px">Drop Time Expiry</div>
                                                                    <div style="display:table-cell;text-align:center;width:37px">:</div>
                                                                    <div style="display:table-cell">{{date('l, d M Y H:i A', strtotime($detail['pickup_order_date']. ' + 3 days'))}}</div>
                                                                </div>
                                                                <div style="display:table-row">
                                                                    <div style="display:table-cell;font-weight:bold;height:21.85px">Amount</div>
                                                                    <div style="display:table-cell;text-align:center;width:37px">:</div>
                                                                    <div style="display:table-cell">Rp {{ number_format($detail['amount']) }}
                                                                        <br>
                                                                        <br>
                                                                    </div>
                                                                </div>
                                                                <div style="display:table-row">
                                                                    <div style="display:table-cell;font-weight:bold;height:21.85px">Drop Off Locker</div>
                                                                    <div style="display:table-cell;text-align:center;width:37px">:</div>
                                                                    <div style="display:table-cell">
                                                                        {{$detail['pickup_locker_name']}}
                                                                        <br> {{$locker_detail->address_2}}, {{$locker_detail->operational_hours}}
                                                                        <br> {{$locker_detail->address}}
                                                                        <br>
                                                                        <br>
                                                                    </div>
                                                                </div>
                                                                <div style="display:table-row">
                                                                    <div style="display:table-cell;font-weight:bold;height:21.85px">Recipient</div>
                                                                    <div style="display:table-cell;text-align:center;width:37px">:</div>
                                                                    <div style="display:table-cell">{{$detail['recipient_name']}} , {{$detail['recipient_phone']}}
                                                                    </div>
                                                                </div>
                                                                <div style="display:table-row">
                                                                    <div style="display:table-cell;font-weight:bold;height:21.85px">Destination Address</div>
                                                                    <div style="display:table-cell;text-align:center;width:37px">:</div>
                                                                    <div style="display:table-cell">{{$detail['recipient_address_detail']}}, {{$detail['recipient_address']}}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <p>
                                                Please find the attached PopSend invoice for the details order. If for some reason you can not download the invoice file, please click the following link below:
                                            </p>
                                            <div style="text-align:center;padding:30px 0">
                                                <a class="lineButton" href="https://popsend.clientname.id/ord/{{$detail['invoice_id']}}" style="padding:10px 20px 10px 20px; background:#f45151!important; border-radius:3px; -webkit-border-radius:3px; -moz-border-radius:3px; text-decoration:none; color:#ffffff; display:inline-block;" target="_blank">Show PopSend Invoice</a>
                                            </div>
                                            <div style="background:#607D8D;color:#fff;padding:20px 40px 20px 40px;">
                                                <p>
                                                    Step by Step:
                                                    <br>
                                                    <ol>
                                                        <li>Print the invoice and stick it to the parcel or write down clearly the invoice number and the destination address.</li>
                                                        <li>Go to PopBox Locker and open "PARCEL DELIVERY" menu.</li>
                                                        <li>Scan the QR order number or manually enter the barcode number (e.g. "PLA1234567890").</li>
                                                    </ol>
                                                </p>
                                                <p>Please drop your parcel before the drop time expires.</p>
                                            </div>
                                            <p style="padding-bottom:53px">
                                                You can contact our <a href="mailto:info@clientname.id" style="color:#607D8D;text-decoration:none" target="_blank">Customer Service</a> for more information.<br>Thank You.
                                            </p>
                                            <p style="padding-bottom:30px">
                                                <span style="display:block;height:30px">Sincerely,</span>
                                                <a href="https://popsend.clientname.id" style="color:#F9474F;text-decoration:none;font-weight:bold" target="_blank">PopSend</a>
                                                <br> PT PopBox Asia Services
                                            </p>
                                            <hr>
                                            <div>
                                                <p style="font-weight:bold; font-size:14px; color:#444; text-align: center;">&copy; 2016 PopBox Asia Services<br>
                                                <br>
                                                        <a href="https://twitter.com/popbox_asia" target="_blank"><img src="https://popsend.clientname.id/img/email/logo-twitter.png"></a>
                                                        <a href="https://www.facebook.com/popbox_asia" target="_blank"><img src="https://popsend.clientname.id/img/email/logo-fb.png"></a>
                                                        <a href="https://www.instagram.com/popbox_asia/" target="_blank"><img src="https://popsend.clientname.id/img/email/logo-insta.png"></a>
                                                </p>
                                            </div>
                                </td>
                            </tr>

                    </table>
                </td>
            </tr>
        </tbody>
    </table>
</body>

</html>
