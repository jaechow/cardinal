<?php
// API Credentials
// Replace the following values between the '' marks with your own:
$cardinalApiKey = '754be3dc-10b7-471f-af31-f20ce12b9ec1';
$cardinalApiIdentifier = '582e0a2033fadd1260f990f6';
$cardinalApiOrgUnitId = '582be9deda52932a946c45c4';

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
?>
<html>
<head>
    <title>PSD2 Example</title>
    <script src="https://songbirdstag.cardinalcommerce.com/cardinalcruise/v1/songbird.js"></script>
    <link href="https://getbootstrap.com/docs/4.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <LINK REL="SHORTCUT ICON" HREF="favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
</head>
<body>
    <form name="myForm" method="post" action="">
        <center>
            <div class="col-md-6 mb-3">
                <label for="jwtClaims">JWT Claims</label>
                <pre id="jwtClaims" style="font-size: xx-small;"><?php print_r($_SESSION['jwtClaims']); ?></pre>
        </div>
            <div class="col-md-6 mb-3">
                <label for="JWTContainer">JWT</label>
                <pre id="jwt" style="font-size: xx-small;"><?php echo $jwt; ?></pre>
                <input type=hidden id="JWTContainer"class="form-control" name="JWTContainer" value='<?php echo $jwt;?>'><br />
            </div>
            <div class="col-md-6 mb-3">
                <label for="customer_credit_card_number">PAN</label>
                <input name="customer_credit_card_number" id="customer_credit_card_number" data-cardinal-field="AccountNumber" type="text" class="form-control" value="4000000000001000"/>
            </div>
            <div class="col-md-6 mb-3">
                <label for="cc_cvv2_number">CVV2</label>
                <input type="text" name="cc_cvv2_number" id="cc_cvv2_number" class="form-control" value="671" />
            </div>
            <div class="input-group col-md-6 mb-3">
                <label for="cc_expiration_month">Exp Month</label>
                <select class="form-control" name="cc_expiration_month" id="cc_expiration_month">
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                    <option value="6">6</option>
                    <option value="7">7</option>
                    <option value="8">8</option>
                    <option value="9">9</option>
                    <option value="10">10</option>
                    <option value="11" selected>11</option>
                    <option value="12">12</option>
                </select>
            </div>
                <div class="input-group col-md-6 mb-3">
                    <label for="cc_expiration_year">Exp Year</label>
                    <select class="form-control" style="width:50%" name="cc_expiration_year" id="cc_expiration_year">
                        <option value="2018">2018</option>
                        <option value="2019">2019</option>
                        <option value="2020">2020</option>
                        <option value="2021">2021</option>
                        <option value="2022">2022</option>
                        <option value="2023">2023</option>
                        <option value="2024" selected>2024</option>
                        <option value="2025">2025</option>
                        <option value="2026">2026</option>
                        <option value="2027">2027</option>
                        <option value="2028">2028</option>
                        <option value="2029">2029</option>
                        <option value="2030">2030</option>
                        <option value="2012">2031</option>
                        <option value="2013">2032</option>
                        <option value="2014">2033</option>
                        <option value="2015">2034</option>
                        <option value="2016">2035</option>
                    </select>
                </div>
            </div>
            <div class="input-group col-md-6 mb-3">
                <input type="button" name="myButton" id="myButton" value="Check for Results"  class="btn btn-primary btn-lg btn-block" onclick="psd2Payment();">
            </div>
        </center>
    </form>    
</body>
    <script>
        var bin = document.getElementById('customer_credit_card_number').value;
        //console.log('bin is: '+bin);

        var jwt = document.getElementById("JWTContainer").value;
        //console.log('jwt is: '+jwt);

        Cardinal.configure({
            logging: {
                level: "verbose"
            },
                timeout: 8000
            });
        Cardinal.setup("init", {
            jwt: jwt
        });

        Cardinal.on("payments.setupComplete", function () {
        console.log('%cConsumer Authentication Setup Complete.', 'color:green; background-color:LightGreen;');
        });

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
    function psd2Payment(){
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
    }   
    </script>
</html>