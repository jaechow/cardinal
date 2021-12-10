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
