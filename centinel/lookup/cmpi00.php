<?php
header('Content-Type: text/plain');
$Timestamp = round(microtime(true) * 1000);
$ApiKey = '754be3dc-10b7-471f-af31-f20ce12b9ec1';
$ApiId = '582e0a2033fadd1260f990f6';
$OrgUnit = '582be9deda52932a946c45c4';
$preHash = $Timestamp.$ApiKey;
$hashed = hash("sha256", $preHash, true);
$Signature = base64_encode($hashed);
$cmpi_lookup = <<<XML
<CardinalMPI>
    <Algorithm>SHA-256</Algorithm>
    <Amount>12345</Amount>
    <BillAddrPostCode>44060</BillAddrPostCode>
    <BillAddrState>OH</BillAddrState>
    <BillingAddress1>8100 Tyler Blvd</BillingAddress1>
    <BillingAddress2></BillingAddress2>
    <BillingCity>Mentor</BillingCity>
    <BillingCountryCode>840</BillingCountryCode>
    <BillingFirstName>John</BillingFirstName>
    <BillingFullName>John Doe</BillingFullName>
    <BillingLastName>Doe</BillingLastName>
    <BillingPostalCode>44060</BillingPostalCode>
    <BillingState>OH</BillingState>
    <BrowserColorDepth>32</BrowserColorDepth>
    <BrowserHeader>text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8</BrowserHeader>
    <BrowserHeader>text/html,application/xhtml+xml,application/xml;q=0.9,</BrowserHeader>
    <BrowserJavaEnabled>true</BrowserJavaEnabled>
    <BrowserLanguage>English</BrowserLanguage>
    <BrowserScreenHeight>980</BrowserScreenHeight>
    <BrowserScreenWidth>1080</BrowserScreenWidth>
    <BrowserTimeZone>25200</BrowserTimeZone>
    <CardExpMonth>02</CardExpMonth>
    <CardExpYear>2024</CardExpYear>
    <CardNumber>4000000000001091</CardNumber>
    <CurrencyCode>840</CurrencyCode>
    <DFReferenceId>c17dea31-9cf6-0c1b8f2d3c5</DFReferenceId>
    <DeviceChannel>browser</DeviceChannel>
    <Email>cardinal.mobile.test@gmail.com</Email>
    <IPAddress>67.17.219.20</IPAddress>
    <Identifier>$ApiId</Identifier>
    <MsgType>cmpi_lookup</MsgType>
    <OrderNumber>order-0001</OrderNumber>
    <OrgUnit>$OrgUnit</OrgUnit>
    <ShippingAddress1>8100 Tyler Blvd</ShippingAddress1>
    <ShippingAddress2></ShippingAddress2>
    <ShippingCity>44060</ShippingCity>
    <ShippingCountryCode>840</ShippingCountryCode>
    <ShippingPostalCode>44060</ShippingPostalCode>
    <ShippingState>OH</ShippingState>
    <Signature>$Signature</Signature>
    <Timestamp>$Timestamp</Timestamp>
    <TransactionType>C</TransactionType>
    <UserAgent>Mozilla/5.0 (Windows NT 6.1; WOW64; rv:30.0) Gecko/20100101 Firefox/30.0</UserAgent>
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
echo $result;

?>