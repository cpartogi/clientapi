<?php
use App\Http\Helpers\Helpdesk;
?>
<style media="screen">
    table tr td {
        padding: 5px;
    }
</style>
<table>
    <tr>
        <td style="width:600px;">
            <table>
                <tr>
                    <td style="font-size: 22px;color: #000;">PopSend Invoice</td>
                </tr>
                <tr>
                    <td style="font-size: 23px;font-weight: bold;">
                        Order {{$detail['invoice_id']}}
                    </td>
                </tr>
            </table>
            <br/>
            <br/>
            <table>
                <tr>
                    <td><strong>Order Created</strong></td>
                    <td>:</td>
                    <td> {{date('l, d M Y H:i A', strtotime($detail['pickup_order_date']))}}</td>
                </tr>
                <tr>
                    <td><strong>Drop Time Expiry</strong></td>
                    <td> : </td>
                    <td>{{date('l, d M Y H:i A', strtotime($detail['pickup_order_date']. ' + 3 days'))}}</td>
                </tr>
                <tr>
                    <td><strong>Amount</strong></td>
                    <td> : </td>
                    <td>Rp {{number_format($detail['amount'])}}</td>
                </tr>
            </table>
        </td>
        <td style="padding-top:100px">
             <?php echo DNS2D::getBarcodeHTML($detail['invoice_id'], "QRCODE",6,6) ?>
        </td>
    </tr>
</table>
<br><br>
<table>
    <tr>
        <td style="border: 1px solid #000;">
            <table>
                <tr>
                    <td width="50%" style="padding:10px">
                        Step by Step.<br/>
                        1. Print the invoice and stick it to parcel or write down clearly the invoice number and the destination <br/>
                        2. Go to Popbox Locker and open "PARCEL DELIVERY" menu.<br/>
                        3. Scan the QR order number or manually enter the barcode number (e.g 'PLA1234567890')
                    </td>
                    <td style="padding:10px">
                        Step by Step.<br/>
                        1. Cetak faktur ini dan tempelkan pada paket atau tulis nomor order dan alamat tujuan pada paket dengan jelas.
                        2. Datang ke loker PopBox dan buka menu "MENGIRIM BARANG".<br/>
                        3. Scan nomor order atau masukkan manual nomor order (misal : 'PLA1234567890')
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<br><br>
<table>
    <tr>
        <td><b>From / Dari</b></td>
        <td><b>TO / KE</b></td>
    </tr>
    <tr>
        <td><b>Popbox Locker</b></td>
        <td><b>Address</b></td>
    </tr>
    <tr>
        <td style="width:50%;padding:10px">
            <b>{{$detail['pickup_locker_name']}}</b><br/>
            {{$locker_detail->address_2}}, {{$locker_detail->operational_hours}}<br/>
            <u>Address /Alamat :</u><br/>
            {{$locker_detail->address}}
        </td>
        <td style="width:50%;padding:10px">
            <b>{{$detail['recipient_address_detail']}}</b><br/>
            {{$detail['recipient_address']}}<br/>
            <u>Recipient /Penerima</u><br/>
            {{$detail['recipient_name']}}<br/>
            {{$detail['recipient_phone']}}
        </td>
    </tr>
</table>
<br>
<table>
    <tr>
        <td style="border-top: 1px solid #999999;width:1000px"></td>
    </tr>
</table>
<br/>
<table>
    <tr>
        <td style="width:50%">
            <b>Important Notes</b>:/ Catatan Penting<br/>
            <b>Please drop your parcel before the drop time expires.</b>
            Mohon Meletakkan paket anda diloker sebelum batas waktu
        </div>
    </td>
    <td class="text-align:right;">
        <div><b>Need help? CS: +62 21 2902 2537/8</b></div>
    </td>
</tr>
</table>
