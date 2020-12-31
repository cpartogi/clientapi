<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
    "http://www.w3.org/TR/html4/loose.dtd">

<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html" charset="UTF-8">
        <title>H2H Partner - Sign On Partner</title>
        <script type="text/javascript" src="http://crypto-js.googlecode.com/files/2.0.0-crypto-sha1.js"></script>
        <script type="text/javascript" src="http://crypto-js.googlecode.com/files/2.0.0-hmac-min.js"></script>
        <script type="text/javascript">
            /**
             *
             *  Secure Hash Algorithm (SHA1)
             *  http://www.webtoolkit.info/
             *
             **/

            function SHA1(msg) {

                function rotate_left(n, s) {
                    var t4 = (n << s) | (n >>> (32 - s));
                    return t4;
                }
                ;

                function lsb_hex(val) {
                    var str = "";
                    var i;
                    var vh;
                    var vl;

                    for (i = 0; i <= 6; i += 2) {
                        vh = (val >>> (i * 4 + 4)) & 0x0f;
                        vl = (val >>> (i * 4)) & 0x0f;
                        str += vh.toString(16) + vl.toString(16);
                    }
                    return str;
                }
                ;

                function cvt_hex(val) {
                    var str = "";
                    var i;
                    var v;

                    for (i = 7; i >= 0; i--) {
                        v = (val >>> (i * 4)) & 0x0f;
                        str += v.toString(16);
                    }
                    return str;
                }
                ;


                function Utf8Encode(string) {
                    string = string.replace(/\r\n/g, "\n");
                    var utftext = "";

                    for (var n = 0; n < string.length; n++) {

                        var c = string.charCodeAt(n);

                        if (c < 128) {
                            utftext += String.fromCharCode(c);
                        }
                        else if ((c > 127) && (c < 2048)) {
                            utftext += String.fromCharCode((c >> 6) | 192);
                            utftext += String.fromCharCode((c & 63) | 128);
                        }
                        else {
                            utftext += String.fromCharCode((c >> 12) | 224);
                            utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                            utftext += String.fromCharCode((c & 63) | 128);
                        }

                    }

                    return utftext;
                }
                ;

                var blockstart;
                var i, j;
                var W = new Array(80);
                var H0 = 0x67452301;
                var H1 = 0xEFCDAB89;
                var H2 = 0x98BADCFE;
                var H3 = 0x10325476;
                var H4 = 0xC3D2E1F0;
                var A, B, C, D, E;
                var temp;

                msg = Utf8Encode(msg);

                var msg_len = msg.length;

                var word_array = new Array();
                for (i = 0; i < msg_len - 3; i += 4) {
                    j = msg.charCodeAt(i) << 24 | msg.charCodeAt(i + 1) << 16 |
                            msg.charCodeAt(i + 2) << 8 | msg.charCodeAt(i + 3);
                    word_array.push(j);
                }

                switch (msg_len % 4) {
                    case 0:
                        i = 0x080000000;
                        break;
                    case 1:
                        i = msg.charCodeAt(msg_len - 1) << 24 | 0x0800000;
                        break;

                    case 2:
                        i = msg.charCodeAt(msg_len - 2) << 24 | msg.charCodeAt(msg_len - 1) << 16 | 0x08000;
                        break;

                    case 3:
                        i = msg.charCodeAt(msg_len - 3) << 24 | msg.charCodeAt(msg_len - 2) << 16 | msg.charCodeAt(msg_len - 1) << 8 | 0x80;
                        break;
                }

                word_array.push(i);

                while ((word_array.length % 16) != 14)
                    word_array.push(0);

                word_array.push(msg_len >>> 29);
                word_array.push((msg_len << 3) & 0x0ffffffff);


                for (blockstart = 0; blockstart < word_array.length; blockstart += 16) {

                    for (i = 0; i < 16; i++)
                        W[i] = word_array[blockstart + i];
                    for (i = 16; i <= 79; i++)
                        W[i] = rotate_left(W[i - 3] ^ W[i - 8] ^ W[i - 14] ^ W[i - 16], 1);

                    A = H0;
                    B = H1;
                    C = H2;
                    D = H3;
                    E = H4;

                    for (i = 0; i <= 19; i++) {
                        temp = (rotate_left(A, 5) + ((B & C) | (~B & D)) + E + W[i] + 0x5A827999) & 0x0ffffffff;
                        E = D;
                        D = C;
                        C = rotate_left(B, 30);
                        B = A;
                        A = temp;
                    }

                    for (i = 20; i <= 39; i++) {
                        temp = (rotate_left(A, 5) + (B ^ C ^ D) + E + W[i] + 0x6ED9EBA1) & 0x0ffffffff;
                        E = D;
                        D = C;
                        C = rotate_left(B, 30);
                        B = A;
                        A = temp;
                    }

                    for (i = 40; i <= 59; i++) {
                        temp = (rotate_left(A, 5) + ((B & C) | (B & D) | (C & D)) + E + W[i] + 0x8F1BBCDC) & 0x0ffffffff;
                        E = D;
                        D = C;
                        C = rotate_left(B, 30);
                        B = A;
                        A = temp;
                    }

                    for (i = 60; i <= 79; i++) {
                        temp = (rotate_left(A, 5) + (B ^ C ^ D) + E + W[i] + 0xCA62C1D6) & 0x0ffffffff;
                        E = D;
                        D = C;
                        C = rotate_left(B, 30);
                        B = A;
                        A = temp;
                    }

                    H0 = (H0 + A) & 0x0ffffffff;
                    H1 = (H1 + B) & 0x0ffffffff;
                    H2 = (H2 + C) & 0x0ffffffff;
                    H3 = (H3 + D) & 0x0ffffffff;
                    H4 = (H4 + E) & 0x0ffffffff;

                }

                var temp = cvt_hex(H0) + cvt_hex(H1) + cvt_hex(H2) + cvt_hex(H3) + cvt_hex(H4);

                return temp.toLowerCase();

            }

            
         function getWords() {
			var msg = document.H2HPartner.clientId.value + document.H2HPartner.SHAREDKEY.value + document.H2HPartner.systrace.value;          
			 document.H2HPartner.words.value = SHA1(msg);     
		} 
            
            function getWordsHMAC() {
                var secretKey = document.H2HPartner.secretkey.value;
                //var basket = 'ITEM 1,'+document.H2HPartner.AMOUNT.value+',1,'+document.H2HPartner.AMOUNT.value+''
                var msg = document.H2HPartner.clientId.value + document.H2HPartner.SHAREDKEY.value + document.H2HPartner.systrace.value;
                var hmac = Crypto.HMAC(Crypto.SHA1, msg, secretKey);
                var hmacBytes = Crypto.HMAC(Crypto.SHA1, msg, secretKey, {asBytes: true});
                var hmacString = Crypto.HMAC(Crypto.SHA1, msg, secretKey, {asString: true});
                //document.H2HPartner.BASKET.value = "ITEM 1,"+document.H2HPartner.AMOUNT.value+",1,"+document.H2HPartner.AMOUNT.value;    
                console.log("hmac       =>" + hmac);
                console.log("hmacBytes  =>" + hmacBytes);
                console.log("hmacString =>" + hmacString);
                document.H2HPartner.ELEMENTWORDS.value = msg;
                document.H2HPartner.words.value = hmac;
                //document.H2HPartner.BASKET.value = basket;
                
                console.log(msg);
            }

            function getTimestamp() {
                var time = new Date().getTime();
                document.H2HPartner.systrace.value = time;
                Date.prototype.yyyymmdd = function() {
                    var yyyy = this.getFullYear().toString();
                    var mm = (this.getMonth() + 1).toString(); // getMonth() is zero-based
                    var dd = this.getDate().toString();
                    var hh = this.getHours().toString();
                    var min = this.getMinutes().toString();
                    var ss = this.getSeconds().toString();

                    return yyyy + (mm[1] ? mm : "0" + mm[0]) + (dd[1] ? dd : "0" + dd[0]) + (hh[1] ? hh : "0" + hh[0]) + (min[1] ? min : "0" + min[0]) + (ss[1] ? ss : "0" + ss[0]);
                };
                d = new Date();
                document.H2HPartner.REQUESTDATETIME.value = d.yyyymmdd();

                var session1 = Math.random().toString(36).substr(2);
                var session2 = Math.random().toString(36).substr(2);
                var session3 = Math.random().toString(36).substr(2);
                document.H2HPartner.SESSIONID.value = session1 + session2 + session3;
            }


        </script>

    </head>
    <body onload="getTimestamp();
                channel();
                isAirline();">        

        <div>
            <!--<form name="H2HPartner" method=post action="http://dev.dokupay.com/dokupay/h2h/signon">-->
            
            <form name="H2HPartner" method=post action="http://dev.dokupay.com/dokupay/h2h/signon">
            
                
                <div>
                    <table width="100%">
                        <tr>
                            <td align="center" colspan="2">SIGN ON PARTNER </td>
                        </tr>
                      
                        <tr>
                            <td align="right">clientId*</td>
                            <td><input type="text" name="clientId" value="1274"/></td>
                        </tr>
                        <tr>
                            <td align="right">clientSecret*</td>
                            <td><input type="text" name="clientSecret" value="0UA74gn5jAwM"/></td>
                        </tr>
                        <tr>
                            <td align="right">systrace*</td>
                            <td><input type="text" name="systrace" value=""/></td>
                        </tr>
                         <tr>
                            <td align="right">responseType</td>
                            <td><select name="responseType" onchange="version();">
                                <option value="">------</option>
                            	<option value="1">1.Json</option>
                            	<option value="2">2.XML</option>
                            	</select>
                        </tr>
                        <tr>
                            <td align="right">version</td>
                            <td><select name="version" onchange="version();">
                                <option value="">----</option>
                            	<option value="1.0">1.0</option>
                            	<option value="2.0">2.0</option>
                            	</select>
                        </tr>
                        
                        <tr>
                            
                            <td><input type="hidden" name="SHAREDKEY" value="fh7Au8FwUL73"/></td>
                            <td><input type="hidden" name="secretkey" value="0UA74gn5jAwM"/></td>
                        </tr>
                        <tr>
                            <td align="right">ELEMENTWORDS</td>
                            <td><input type="text" name="ELEMENTWORDS" size="60" readonly="readonly"/>
                            <br>
                            *encrypted SHA1 HMAC, from element :(clientId + sharedKey + systrace)</td>
                            

                        </tr>
                        <tr>
                            <td align="right">words*</td>
                            <td>
                                <div style="float:left;margin-right:10px">
                                    <input type="text" id="words" name="words" value="" size="60" readonly="readonly"/>
                                </div>
                                <div style="float:left">
                                    <input type="button" value="get WORDS SHA1" onClick="getWords();">&nbsp;
                                    <input type="button" value="get WORDS SHA1 HMAC" onClick="getWordsHMAC();">&nbsp;
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td align="right"></td>
                            <td>
                                <input type="submit" value="submit">
                            </td>
                        </tr>
                     
</html>