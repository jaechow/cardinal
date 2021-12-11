# BIN Intelligence API

Facilitates passing of the BIN (short for Bank ID Number) securely to Cardinal
Commerce by way of secure server-to-server communication.  Ultimately, this step
allows each Issuing Bank to run their required Device Data Collection directly
upon the cardholder device.

## Integration steps

Before proceeding please ensure you have obtained the appropriate
Cardinal Cruise API Credentials and the account is configured for
the BIN Intelligence API (and/or Data Exchange API).

1. Connect to the BIN Intelligence API
2. Send the BIN (or the complete PAN)
3. Handle the response

### Connect to the BIN Intelligence API

In this step we authenticate with the BIN Intelligence API and submit the BIN
inside a JSON request.

To authenticate with the BIN Intelligence API we first need set a variable to
store the current EPOCH timestamp in milliseconds (the milliseconds bit is
important).

```PHP
$Timestamp = round(microtime(true) * 1000);
```

Next we will generate a unique identifier for the request and store that value
as a variable.  (In production you will likely want your order managment
system to dictate the Transaction ID).  For the purposes of this test we will
generate a random number and append it to a static value `txn-0-`

```php
$TrxId = 'txn-0-' . strval(mt_rand(1000, 10000));
```

Lastly we will create a variable to store the API Key value.

```php
$ApiKey = '754be3dc-10b7-471f-af31-f20ce12b9ec1';
```

Authenticating to the API will consist of building a Signature token to act as a
symectric key (this means that Cardinal Commerce creates the expected-token and
compares it with the value that you pass).  The Signature token is the result of
concatenating three values (`TimeStamp`, `TransactionID`,`APIKey`) then hashing,
then base64 encoding the result.
First, we will create a variable to store the concatenated values.

```php
$preHash = $Timestamp.$TrxId.$ApiKey;
```

Next, we use `SHA-256` or `SHA-512` to hash the concatenated value (for this
example, `SHA-512` is used)

```php
$hashed = hash("sha512", $preHash, true);
```

Last, use Base64 to encode the hashed value

```php
$Signature = base64_encode($hashed);
```

### Send the BIN (or the complete PAN)

For this example let's use the BIN `40000100` and assign it to the variable
`$bin`

```php
$bin = '40000100';
```

Next, we will create a function to prepare the json that will be sent in the
request.  This function will populate values from the variables created earlier.

```php
function prepareJson (){
	global $ApiId, $bin, $OrgUnit, $Signature, $Timestamp, $TrxId;

	$theJSON = array(
		"Signature" => $Signature,
		"Timestamp" => $Timestamp,
		"TransactionId" => $TrxId,
		"Identifier" => $ApiId,
		"OrgUnitId" => $OrgUnit,
		"Algorithm" => "SHA-512",
		"Payload" => array("BINs"=>[$bin])
	);
	return $theJSON;
}
```

To build a JSON request make a call to `prepareJson()` and assign a variable
name.

```php
$testPayload = prepareJson();
```

### Handle the response

We will build a simple HTML form to submit the request and handle the response.

```html
<!DOCTYPE html>
<html>
<head>
	<title></title>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
	<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
	<link rel="stylesheet" type="text/css" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">

</head>
<body>
<div id="" style="margin:3% 15%;">
	<div class="card">
	  <div class="card-header">
	    Cardinal Cruise
	  </div>
	  <div class="card-body">
	    <h5 class="card-title">BIN Intelligence API</h5>
	    <p id="theBod"></p>
	    <p><pre id="thePre" style="font-size: xx-small; white-space: pre-wrap; word-break: break-word"></pre></p>
	    <p id="resStatus"></p>
	    <p><pre id="resPre" style="font-size: xx-small; white-space: pre-wrap; word-break: break-word"></pre></p>
	    <form>
	    	<!--
	    	<label for="bin">Bin:</label>
	    	<p><input type="text" class="input-control col-md-4 disabled" id="bin" placeholder="<?php echo $bin ?>"></p>
	    	<p></p>-->
	    	<a href="#" class="btn btn-primary btn-block" onclick="binStuff();">Send BIN</a>
	    </form>
	  </div>
	</div>
</div>

</body>
<script>
	function binStuff(){
		//var bin = document.getElementById('bin').value;
		var foo = '<?php print_r(json_encode($testPayload)); ?>';
		document.getElementById('theBod').innerHTML = "JSON to be posted to BIN Intelligence:";
		document.getElementById('thePre').innerHTML = foo;
		document.getElementById('thePre').classList.add('alert','alert-success');
		jQuery.ajax({
			async:false,
			contentType: "application/json",
		  	timeout:10000,
		  	type:"POST",
		  	url:"https://geostag.cardinalcommerce.com/DeviceFingerprintWeb/V2/Server/Bin/Load",
		  	data:foo
		})
		.done(function(response){
		  		document.getElementById('resStatus').innerHTML = "BIN Intelligence response:";
		  		document.getElementById('resPre').classList.add('alert','alert-success');
		  		document.getElementById('resPre').innerHTML = response;
		    	console.log('Response: %c'+response,'color:green; background-color:LightGreen;');
		    	resParsed = JSON.parse(response);
		    	ddcUrl = resParsed.Payload.DeviceDataCollectionUrl;
		    	refId = 'ReferenceId='+resParsed.Payload.ReferenceId;
		    	console.log(refId);
		  	})
		console.log('JSON request is: %c' +foo, "color:green; background-color:LightGreen");
	}
</script>
</html>
```

## Code Samples

### Putting it all together

```php
<?php

$Timestamp = round(microtime(true) * 1000);
$ApiKey = '754be3dc-10b7-471f-af31-f20ce12b9ec1';
$ApiId = '582e0a2033fadd1260f990f6';
$OrgUnit = '582be9deda52932a946c45c4';
$TrxId = 'txn-0-' . strval(mt_rand(1000, 10000));

$preHash = $Timestamp.$TrxId.$ApiKey;
# Hash the concatenated value (for this example, SHA-512 is used)
$hashed = hash("sha512", $preHash, true);
# Base64 Encode the hashed value
$Signature = base64_encode($hashed);
$bin = '40000100';

function prepareJson (){
	global $Timestamp, $bin, $ApiId, $OrgUnit, $TrxId, $Signature;

	$theJSON = array(
		"Signature" => $Signature,
		"Timestamp" => $Timestamp,
		"TransactionId" => $TrxId,
		"Identifier" => $ApiId,
		"OrgUnitId" => $OrgUnit,
		"Algorithm" => "SHA-512",
		"Payload" => array("BINs"=>[$bin])
	);
	return $theJSON;
}

$testPayload = prepareJson();
?>
```

>...still the same PHP page, just below the closing bracket...

```html
<!DOCTYPE html>
<html>
<head>
	<title></title>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
	<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
	<link rel="stylesheet" type="text/css" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">

</head>
<body>
<div id="" style="margin:3% 15%;">
	<div class="card">
	  <div class="card-header">
	    Cardinal Cruise
	  </div>
	  <div class="card-body">
	    <h5 class="card-title">BIN Intelligence API</h5>
	    <p id="theBod"></p>
	    <p><pre id="thePre" style="font-size: xx-small; white-space: pre-wrap; word-break: break-word"></pre></p>
	    <p id="resStatus"></p>
	    <p><pre id="resPre" style="font-size: xx-small; white-space: pre-wrap; word-break: break-word"></pre></p>
	    <form>
	    	<!--
	    	<label for="bin">Bin:</label>
	    	<p><input type="text" class="input-control col-md-4 disabled" id="bin" placeholder="<?php echo $bin ?>"></p>
	    	<p></p>-->
	    	<a href="#" class="btn btn-primary btn-block" onclick="binStuff();">Send BIN</a>
	    </form>
	  </div>
	</div>
</div>

</body>
<script>
	function binStuff(){
		//var bin = document.getElementById('bin').value;
		var foo = '<?php print_r(json_encode($testPayload)); ?>';
		document.getElementById('theBod').innerHTML = "JSON to be posted to BIN Intelligence:";
		document.getElementById('thePre').innerHTML = foo;
		document.getElementById('thePre').classList.add('alert','alert-success');
		jQuery.ajax({
			async:false,
			contentType: "application/json",
		  	timeout:10000,
		  	type:"POST",
		  	url:"https://geostag.cardinalcommerce.com/DeviceFingerprintWeb/V2/Server/Bin/Load",
		  	data:foo
		})
		.done(function(response){
		  		document.getElementById('resStatus').innerHTML = "BIN Intelligence response:";
		  		document.getElementById('resPre').classList.add('alert','alert-success');
		  		document.getElementById('resPre').innerHTML = response;
		    	console.log('Response: %c'+response,'color:green; background-color:LightGreen;');
		    	resParsed = JSON.parse(response);
		    	ddcUrl = resParsed.Payload.DeviceDataCollectionUrl;
		    	refId = 'ReferenceId='+resParsed.Payload.ReferenceId;
		    	console.log(refId);
		})
		console.log('JSON request is: %c' +foo, "color:green; background-color:LightGreen");
	}
</script>
</html>
```

### Request

```json
{"Signature":"eqfYfQfJWNstGVBtP3+y/CVgKpJW2gmrO8Y8XJzJa4ueRac++4lM7J21tF8wMmvC2R3kES7f8Iq8qjoF4I8/5w==","Timestamp":1639206745404,"TransactionId":"txn-0-6107","Identifier":"582e0a2033fadd1260f990f6","OrgUnitId":"582be9deda52932a946c45c4","Algorithm":"SHA-512","Payload":{"BINs":["40000100"]}}
```

### Response

```json
{"TransactionId":"txn-0-6107","ErrorNumber":0,"Payload":{"ReferenceId":"4c8bf9e0-5065-4c9d-8bd5-cee13abfc2ae","DeviceDataCollectionUrl":"https://centinelapistag.cardinalcommerce.com/V1/Cruise/Collect"}}
```
