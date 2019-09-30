<h1>Cardinal Cruise Standard Tutorial</h1>

__Contents:__

[TOC]

## Introduction

This tutorial and guide will supplement the Cardinal Cruise [Standard Implementation Guide](https://cardinaldocs.atlassian.net/wiki/spaces/CC/pages/7929857/Cardinal+Cruise+Standard) please make sure you have completed the [Getting Started](https://cardinaldocs.atlassian.net/wiki/spaces/CC/pages/131806/Getting+Started) steps and that you have your:

- API Key
- API Identifier
- Org Unit ID

## Generating the JWT

First a payload is generated then the JWT claims are established and everthing is passed to the function, `generate_cruise_jwt` that performs the encryption.
A few keys to keep in mind:

- To build a proper JWT Payload the following values are minimally required:
    + `CurrencyCode`
    + `Amount`
    + `OrderNumber`
- The `Amount` value must not contain a decimal point (eg. $15.00 = 1500)

```php
// Currency variable.  A value for CurrencyCode is needed in two places:
// The JWT Payload and in the Order Object.
$currency = '840';
// Amount variable.  Similar to CurrencyCode, the value for amount is -
// required in the JWT Payload and Order Object.  HOWEVER,
// the format changes.  The JWT Payload requires a non-decimal format,
// the Order Object requires the traditional two-digit-decimal format.
// $Amount is in non-decimal format.
$Amount = '1500';
// $decAmount converts the non-decimal format to tradtitional -
// two-digit-decimal.
$decAmount = number_format($Amount/100, 2);
// Like the two variables above, Order Number is also required in -
// both the JWT Payload and the Order Object.
$OrderNumber = 'ORDER-' . strval(mt_rand(1000, 10000));
// Build the JWT Payload with required elements.
$_SESSION['payload'] = array(
                "OrderDetails" => array(
                    "OrderNumber" =>  $OrderNumber,
                    "Amount" => $Amount,
                    "CurrencyCode" => $currency)
            );
function base64_encode_urlsafe($source) {
    $rv = base64_encode($source);
    $rv = str_replace('=', '', $rv);
    $rv = str_replace('+', '-', $rv);
    $rv = str_replace('/', '_', $rv);
    return $rv;
}

function base64_decode_urlsafe($source) {
    $s = $source;
    $s = str_replace('-', '+', $s);
    $s = str_replace('_', '/', $s);
    $s = str_pad($s, strlen($s) + strlen($s) % 4, '=');
    $rv = base64_decode($s);
    return $rv;
}

function sign_jwt($header, $body) {
    global $cardinalApiKey;
    $plaintext = $header . '.' . $body;
    return base64_encode_urlsafe(hash_hmac(
        'sha256', $plaintext, $cardinalApiKey, true));
}

function generate_jwt($data) {
    $header = base64_encode_urlsafe(json_encode(array(
        'alg' => 'HS256', 'typ' => 'JWT'
    )));
    $body = base64_encode_urlsafe(json_encode($data));
    $signature = sign_jwt($header, $body);
    return $header . '.' . $body . '.' . $signature;
}

function generate_cruise_jwt($payload) {
    global $cardinalApiIdentifier, $cardinalApiOrgUnitId;
    $iat = time();
    $data = array(
        'jti' => uniqid(),
        'iat' => $iat,
        'exp' => $iat + 7200,
        'iss' => $cardinalApiIdentifier,
        'OrgUnitId' => $cardinalApiOrgUnitId,
    );
    // This is important: the CurrencyCode is required in the JWT Payload:
    $payload = 
    $data['Payload'] = $payload;
    $data['ObjectifyPayload'] = true;
    $_SESSION['jwtClaims'] = $data;
    $rv = generate_jwt($data);
    return $rv;
}

function parse_cruise_jwt($jwt) {
    $split = explode('.', $jwt);
    if (count($split) != 3) {
        return;
    }
    list($header, $body, $signature) = $split;
    if ($signature != sign_jwt($header, $body)) {
        return;
    }
    $payload = json_decode(base64_decode_urlsafe($body));
    return $payload;
}

// Generate JWT complete with JWT Payload.
$jwt = generate_cruise_jwt($_SESSION['payload']);

```

## Include the Script

This will likely be the quickest step.  Add the Songbird javascript to the page.

```html
<script src="https://songbirdstag.cardinalcommerce.com/cardinalcruise/v1/songbird.js"></script>
```

## Configure Songbird

In order to configure Songbird with the minimal set of options, set the `logging:{level}` to `verbose` and the `timeout` value to `8000` (for the puposes of this tutorial).
Many more configuration options [here](https://cardinaldocs.atlassian.net/wiki/spaces/CC/pages/1409568/Configurations)

```javascript
Cardinal.configure({
    logging: {
        level: "verbose"
    },
        timeout: 8000
    });
```
## Listen for Events

The `Cardinal.on` function sets up event subscriptions.  This helps control sequencing of functions with events.
In this tutorial, the `payments.setupComplete` and `payments.validated` events are used to trigger log comments in the browser console confirming the event took place.
The following snippet from the tutorial confirms when the `payments.setupComplete` event has triggered (the result of executing `Cardinal.setup('init')`):

```javascript
Cardinal.on("payments.setupComplete", function () {
console.log('%cConsumer Authentication Setup Complete.', 'color:green; background-color:LightGreen;');
});
```
Here, again for the purpose of this tutorial, the `payments.validated` event subscription will trigger notifications in the browser console.  First, a check is made whether `Validated` exists in the response data, `decodedResponseData`.  If so, a second check is made for `ECIFlag` and `Enrolled` values.  If either of the conditions are met, the console logs the event and confirms that Consumer Authentication has successfully completed.
If `ECIFlag`  is any other value while `Validated` is still true, a console log entry will be made to confirm the Consumer Authentication is complete however no benefits or liablity shift will apply.  Last, if `Validated` is false a log entry will be made to confirm that Consumer Authentication failed.

```javascript
Cardinal.on('payments.validated', function(decodedResponseData, responseJWT){
    if(decodedResponseData.Validated){
        var eci = decodedResponseData.Payment.ExtendedData.ECIFlag;
        var enrolled = decodedResponseData.Payment.ExtendedData.Enrolled;
        if (eci == '05' || eci == '06' || enrolled == 'B') {
            if (enrolled == 'B') {
                console.warn('Consumer Authentication was %cBYPASSED','color:yellow; background-color:orange');
            }
            console.log("payments.validated results: " + JSON.stringify(decodedResponseData, null, '    '));
            console.log('%cConsumer Authentication Completed Successfully.','color:green; background-color:LightGreen');
        } else {
            console.warn("payments.validated results: " + JSON.stringify(decodedResponseData, null, '    '));
            console.warn('%cConsumer Authentication should be considered incompleted.  Do not proceed to Authorization.','color:red');
        }
    } else {
        console.warn('%cConsumer Authentication Failed.', 'color:white; background-color:red');
    }
});
```

## Initialize It

This function initiates the communication process with Cardinal.  Here, the variables `bin` and `jwt` are created by returning the values from their respective elements.

```javascript
var bin = document.getElementById('customer_credit_card_number').value;
//console.log('bin is: '+bin);

var jwt = document.getElementById("JWTContainer").value;
//console.log('jwt is: '+jwt);

```
The `jwt` variable is sent in the `Cardinal.setup('init')` function.
```javascript
Cardinal.setup("init", {
    jwt: jwt
});
```
## BIN Detection

There are two recommended paths based on merchant need.  If the card holder's always type their own card number (PAN will always originate from client front-end) the [field decorator](https://cardinaldocs.atlassian.net/wiki/spaces/CC/pages/311984510/BIN+Detection#BINDetection-Style1-FieldDecorator) is recommended:

```html
<input name="customer_credit_card_number" id="customer_credit_card_number" data-cardinal-field="AccountNumber" type="text" class="form-control"  />
```

If the payment instrument is tokenized or otherwise vaulted, the BIN (first 6 to 8 digits of the PAN) will need to be returned by the merchant to the client front-end at which point the `bin.process` trigger may initiate.
Here, in this tutorial, the `bin.process` trigger is the controller for the `Cardinal.start()` function.  It should also be pointed out: for the purpose of this tutorial, both styles have been employed.  This is unnecessary in production.

```javascript
Cardinal.trigger("bin.process", bin).then(function(results){
if(results.Status){
   console.log('%cBin Profiling Complete.', 'color:green; background-color:LightGreen;');
   Cardinal.start("cca", order);
}else{
   console.warn('BIN Profiling failed.  Continuing without Device Data.');
   Cardinal.start("cca", order);
}
}).catch(function(error){
    console.warn('An error occurred during BIN Profiling.');
});
```

## Order Object

The object containing order specifics including the full PAN, expiration date, CVV2, billing/shipping info etc.  The following [JSON full Order Object](https://cardinaldocs.atlassian.net/wiki/spaces/CC/pages/32950/Request+Objects#RequestObjects-ExampleJSON) demonstrates all available fields.

```javascript
var order = {
    Consumer: {
        Account: {
            AccountNumber: document.getElementById('customer_credit_card_number').value,
            CardCode: document.getElementById('cc_cvv2_number').value,
            ExpirationMonth: document.getElementById('cc_expiration_month').value,
            ExpirationYear: document.getElementById('cc_expiration_year').value,
            NameOnAccount: "Test E Testface"
        },
        BillingAddress: {
            FirstName: "Test E.",
            LastName: "Testface",
            Address1: "8100 Tyler Blvd.",
            City: "Mentor",
            State: "OH",
            PostalCode: "44060",
            CountryCode: "US",
            Phone1: "8773528444"
        },
        OrderDetails: {
            Amount: "<?php echo $decAmount; ?>",
            CurrencyCode: "<?php echo $currency; ?>",
            OrderNumber: "<?php echo $OrderNumber; ?>"
        },
    }
}
```
## Start Cardinal Consumer Authentication

In the context of this tutorial, the `Cardinal.start()` function was integrated inside the `bin.process` trigger to ensure BIN Detection initiates first, before `Cardinal.start()`.

```javascript
Cardinal.trigger("bin.process", bin).then(function(results){
if(results.Status){
   console.log('%cBin Profiling Complete.', 'color:green; background-color:LightGreen;');
   Cardinal.start("cca", order);
}else{
   console.warn('BIN Profiling failed.  Continuing without Device Data.');
   Cardinal.start("cca", order);
}
}).catch(function(error){
    console.warn('An error occurred during BIN Profiling.');
});
```

## Handling Cardinal Consumer Authentication Response

For this rudimentary tutorial, the return values for `ECIFlag` and `Enrolled` drive the logic that controls the browser console notifications.  Taking the next step should involve logic to evaluate the return value `ActionCode`.

```javascript
Cardinal.on('payments.validated', function(decodedResponseData, responseJWT){
    if(decodedResponseData.Validated){
        var eci = decodedResponseData.Payment.ExtendedData.ECIFlag;
        var enrolled = decodedResponseData.Payment.ExtendedData.Enrolled;
        if (eci == '05' || eci == '06' || enrolled == 'B') {
            if (enrolled == 'B') {
                console.warn('Consumer Authentication was %cBYPASSED','color:yellow; background-color:orange');
            }
            console.log("payments.validated results: " + JSON.stringify(decodedResponseData, null, '    '));
            console.log('%cConsumer Authentication Completed Successfully.','color:green; background-color:LightGreen');
        } else {
            console.warn("payments.validated results: " + JSON.stringify(decodedResponseData, null, '    '));
            console.warn('%cConsumer Authentication should be considered incompleted.  Do not proceed to Authorization.','color:red');
        }
    } else {
        console.warn('%cConsumer Authentication Failed.', 'color:white; background-color:red');
    }
});
```
