<?php
require_once '../../vendor/autoload.php';
require_once '../../data/config.php';
$faker = Faker\Factory::create();
ini_set('precision', 18);
//header('Content-Type: text/plain');
$Timestamps = round(microtime(true));
$Timestamp = date_create()->format('Uv');
$ApiKey = $cruise['ApiKey'];
$ApiId = $cruise['ApiId'];
$OrgUnit = $cruise['OrgUnit'];
$preHash = $Timestamp.$ApiKey;
$hashed = hash("sha512", $preHash, true);
$Signature = base64_encode($hashed);
$expireYear = date("Y")+4;
$refId = 'c17dea31-9cf6-0c1b8f2d3c5';
$cmpi_lookup = <<<XML
<CardinalMPI>
    <Algorithm>SHA-512</Algorithm>
    <Amount>12345</Amount>
    <BillAddrPostCode>$faker->postcode</BillAddrPostCode>
    <BillAddrState>$faker->stateAbbr</BillAddrState>
    <BillingAddress1>$faker->streetAddress</BillingAddress1>
    <BillingAddress2></BillingAddress2>
    <BillingCity>$faker->city</BillingCity>
    <BillingCountryCode>840</BillingCountryCode>
    <BillingFirstName>$faker->firstName</BillingFirstName>
    <BillingLastName>$faker->lastName</BillingLastName>
    <BillingPostalCode>44060</BillingPostalCode>
    <BillingState>$faker->stateAbbr</BillingState>
    <BrowserColorDepth>32</BrowserColorDepth>
    <BrowserHeader>text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8</BrowserHeader>
    <BrowserJavaEnabled>true</BrowserJavaEnabled>
    <BrowserLanguage>en-US</BrowserLanguage>
    <BrowserScreenHeight>980</BrowserScreenHeight>
    <BrowserScreenWidth>1080</BrowserScreenWidth>
    <BrowserTimeZone>420</BrowserTimeZone>
    <CardExpMonth>02</CardExpMonth>
    <CardExpYear>$expireYear</CardExpYear>
    <CardNumber>4000000000001091</CardNumber>
    <CurrencyCode>840</CurrencyCode>
    <DFReferenceId>$refId</DFReferenceId>
    <DeviceChannel>browser</DeviceChannel>
    <Email>$faker->email</Email>
    <IPAddress>$faker->ipv4</IPAddress>
    <Identifier>$ApiId</Identifier>
    <MobilePhone>$faker->e164PhoneNumber</MobilePhone>
    <MsgType>cmpi_lookup</MsgType>
    <OrderNumber>$faker->uuid</OrderNumber>
    <OrgUnit>$OrgUnit</OrgUnit>
    <ReturnUrl>https://postman-echo.com/post</ReturnUrl>
    <ShippingAddress1>$faker->streetAddress</ShippingAddress1>
    <ShippingAddress2></ShippingAddress2>
    <ShippingCity>$faker->city</ShippingCity>
    <ShippingCountryCode>840</ShippingCountryCode>
    <ShippingPostalCode>$faker->postcode</ShippingPostalCode>
    <ShippingState>$faker->stateAbbr</ShippingState>
    <Signature>$Signature</Signature>
    <Timestamp>$Timestamp</Timestamp>
    <TransactionType>C</TransactionType>
    <UserAgent>$faker->userAgent()</UserAgent>
    <Version>1.7</Version>
</CardinalMPI>
XML;

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL,"https://centineltest.cardinalcommerce.com/maps/txns.asp");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'cmpi_msg='."\n".urlencode($cmpi_lookup)."\n");
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLINFO_HEADER_OUT, 1);

$result = curl_exec($ch);
curl_close ($ch);
//echo $result;
$xmldata = simplexml_load_string($result);
$jsondata = json_encode($xmldata);
$json = json_decode($jsondata,TRUE);
//print_r($jsondata);
$acsUrl = $json['ACSUrl'];
$payload = $json['Payload'];
$trxId = $json['TransactionId'];

//echo $cmpi_lookup;

function base64url_encode($input) {
 return rtrim(strtr(base64_encode($input), '+/', '-_'), '='); 
}

function base64url_decode($input) {
 return base64_decode(str_pad(strtr($input, '-_', '+/'), strlen($input) + (4 - strlen($input) % 4) % 4, '=', STR_PAD_RIGHT));
}
//build jwt headers
$headers = ['alg'=>'HS256','typ'=>'JWT'];
$headers_encoded = base64url_encode(json_encode($headers));
//build jwt payload (for postMessage)
$jwt = ['jti'=>$trxId,'iat'=>$Timestamps, 'iss'=>$ApiId, 'OrgUnitId'=>$OrgUnit, 'ObjectifyPayload'=>true,  'Payload'=>['ACSUrl'=>$acsUrl, 'Payload'=>$payload,'TransactionId'=>$trxId],'ReferenceId'=>$refId];
/* to use jwt for returnUrl, comment-out the above $jwt and uncomment the following */
//$jwt = ['jti'=>$trxId,'iat'=>$Timestamps, 'iss'=>$ApiId, 'OrgUnitId'=>$OrgUnit, 'ObjectifyPayload'=>true,  'Payload'=>['ACSUrl'=>$acsUrl, 'Payload'=>$payload,'TransactionId'=>$trxId],'ReferenceId'=>$refId, 'ReturnUrl'=>'https://postman-echo.com/post'];
$jwt_encoded = base64url_encode(json_encode($jwt));
//build jwt signature
$key = $ApiKey;
$signature = hash_hmac('sha256',"$headers_encoded.$jwt_encoded",$key,true);
$signature_encoded = base64url_encode($signature);

//build and return the token
$token = "$headers_encoded.$jwt_encoded.$signature_encoded";
//echo $token;
$url = 'https://centinelapistag.cardinalcommerce.com/V2/Cruise/StepUp';

?>

<HTML>
<head>
    <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no" />
    <style>
      .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            width: 60%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            display: none;
        }
    </style>
    <script>
        function openModal() {
            document.getElementById('modal').style.display = 'flex';
            document.getElementById('modalContent').style.display = 'block';
        }
        function closeModal() {
            document.getElementById('modal').style.display = 'none';
            document.getElementById('modalContent').style.display = 'none';
        }
    </script>
</head>
<pre id="response"></pre>
<pre id="payload"></pre>
<pre><?php echo $token ?></pre>
<div id="modal" class="modal">
        <div id="modalContent" class="modal-content">
            <iframe name="challenge-iframe" id="challenge-iframe" height="100%" width="100%" style="visibility: hidden; border: 0;">
            </iframe>
        </div>
</div>

<form id="challenge-form" target="challenge-iframe" method="POST" action="<?php echo $url ?>">
<input type="hidden" name="JWT" value="<?php echo $token ?>" />
</form>
<script>
    var response = <?php echo $jsondata ?>;
    var payload = <?php echo json_encode($jwt) ?>;
    document.getElementById("response").textContent = JSON.stringify(response, undefined, 2);
    document.getElementById("payload").textContent = JSON.stringify(payload, undefined, 2);
</script>
<script>window.onload = function () {
      // Auto submit form on page load
      document.getElementById('challenge-form').submit();
    }
    const cruiseApiOrigin = 'https://centinelapistag.cardinalcommerce.com'
    /**
     * NOTE: This event binding will not work in older IE browsers.
     * You will need to also implement attachEvent if you need to support older IE browsers.
     * https://developer.mozilla.org/en-US/docs/Web/API/EventTarget/addEventListener#legacy_internet_explorer_and_attachevent
     **/
    window.addEventListener('message', (evnt) => {
      try {
        // Filter postMessage events by origin and process only the Cardinal events
        if (evnt.origin === cruiseApiOrigin) {
          // CruiseAPI events are stringified JSON objects to ensure backwards compatibility with older browsers
          let data = JSON.parse(evnt.data)
          if (data !== undefined && data.MessageType !== undefined) {
            // Do merchant logic
            switch(data.MessageType)
            {
              case 'stepUp.acsRedirection':
                console.log('%cEvent: '+data.MessageType,'color:purple; background-color:Orange');
                openModal();
                document.getElementById('challenge-iframe').style.visibility = "visible";
                break;
              case 'stepUp.completion':
                console.log('%cEvent: '+data.MessageType,'color:green; background-color:LightGreen');
                closeModal();
                document.getElementById('challenge-iframe').style.visibility = "hidden";
                console.log('%cTransaction ID: '+data.TransactionId,'color:green; background-color:LightGreen');
                break;
              case 'stepUp.error':
                console.log(data.MessageType);
                closeModal();
                document.getElementById('challenge-iframe').style.visibility = "hidden";
                break;
              default:
                console.error("Unknown MessageType found ["+data.MessageType+"]");
                // Implement Merchant logic - Handle unknown MessageType
                break;
            }
          }
        }
      } catch (e) {
        console.error('failed to parse CruiseAPI postMessage event', e)
      }
    }, false)
</script>
</HTML>

