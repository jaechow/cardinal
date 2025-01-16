<?php
require_once '../../data/config.php';
$Timestamp = round(microtime(true) * 1000);
$Timestamps = round(microtime(true));
$ApiKey = $cruise['ApiKey'];
$ApiId = $cruise['ApiId'];
$OrgUnit = $cruise['OrgUnit'];
$TrxId = 'txn-0-' . strval(mt_rand(1000, 10000));
$preHash = $Timestamp.$TrxId.$ApiKey;
# Hash the concatenated value (for this example, SHA-512 is used)
$hashed = hash("sha512", $preHash, true);
# Base64 Encode the hashed value
$Signature = base64_encode($hashed);
$bin = $testCard['DAF-tc1a'];

function prepareJson (){
	global $Timestamp, $bin, $ApiId, $OrgUnit, $TrxId, $Signature;

	$theJSON = array(
		"Algorithm" => "SHA-512",
		"Identifier" => $ApiId,
		"OrgUnitId" => $OrgUnit,
		"Payload" => array("AccountNumber" => $bin),
		"Signature" => $Signature,
		"Timestamp" => $Timestamp,
		"TransactionId" => $TrxId	
	);
	return $theJSON;
}
$url = 'https://dataexchangestag.cardinalcommerce.com/V1/AccountNumber/GetInfo';
$testPayload = prepareJson();
$postdata = json_encode($testPayload, JSON_PRETTY_PRINT);
$ch = curl_init($url); 
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
$result = json_decode(curl_exec($ch),true);
curl_close($ch);
$final = json_encode($result, JSON_PRETTY_PRINT);
//echo $postdata . "\n\n";
//echo $final;
$referenceID = $result['Payload']['ReferenceId'];
echo "\n\n";
//echo $referenceID;
function base64url_encode($input) {
 return rtrim(strtr(base64_encode($input), '+/', '-_'), '='); 
}

function base64url_decode($input) {
 return base64_decode(str_pad(strtr($input, '-_', '+/'), strlen($input) + (4 - strlen($input) % 4) % 4, '=', STR_PAD_RIGHT));
}
//build jwt headers
$headers = ['alg'=>'HS256','typ'=>'JWT'];
$headers_encoded = base64url_encode(json_encode($headers));
//build jwt payload
$payload = ['jti'=>$TrxId,'iat'=>$Timestamps, 'iss'=>$ApiId, 'OrgUnitId'=>$OrgUnit, 'ObjectifyPayload'=>true, 'ReferenceId'=>$referenceID,'ReturnUrl'=>'https://c32c0d3d-5c32-4adc-a78c-63d3ce1e05c3.mock.pstmn.io'];

$payload_encoded = base64url_encode(json_encode($payload));
//build jwt signature
$key = $ApiKey;
$signature = hash_hmac('sha256',"$headers_encoded.$payload_encoded",$key,true);
$signature_encoded = base64url_encode($signature);

//build and return the token
$token = "$headers_encoded.$payload_encoded.$signature_encoded";
//echo $token;
$url = 'https://centinelapistag.cardinalcommerce.com/V2/Cruise/Collect';

?>
<HTML>
<head>
	<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no" />
</head>
<pre id="request"></pre>
<pre id="result"></pre>
<pre id="payload"></pre>
<pre><?php echo $token ?></pre>
<iframe name="ddc-iframe" height="10" width="10" style="display: none;">
</iframe>

<form id="ddc-form" target="ddc-iframe" method="POST" action="https://centinelapistag.cardinalcommerce.com/V2/Cruise/Collect">
<input type="hidden" name="JWT" value="<?php echo $token ?>" />
</form>
<script>
	var request = <?php echo $postdata ?>;
	var data = <?php echo $final ?>;
	var payload = <?php echo json_encode($payload) ?>;
	document.getElementById("request").textContent = JSON.stringify(request, undefined, 2);
	document.getElementById("result").textContent = JSON.stringify(data, undefined, 2);
	document.getElementById("payload").textContent = JSON.stringify(payload, undefined, 2);
</script>
<script>window.onload = function () {
      // Auto submit form on page load
      document.getElementById('ddc-form').submit();
    }
</script>
</HTML>
