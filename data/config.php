<?php
# Credential configuration file for the test suite
$cruise['ApiKey'] = "754be3dc-10b7-471f-af31-f20ce12b9ec1";
$cruise['ApiId'] = "582e0a2033fadd1260f990f6";
$cruise['OrgUnit'] = "582be9deda52932a946c45c4";

# Test card numbers
# Full list of test card numbers can be found at:
# https://cardinaldocs.atlassian.net/wiki/spaces/CCen/pages/903577725/EMV+3DS+Test+Cases

# Successful Frictionless Authentication
$testCard['EMV-tc1'] = "4000000000002701";
# Successful Step Up Authentication
$testCard['EMV-tc9'] = "4000000000002503";
# Successful Step-Up Authentication for VPP Enrollment
$testCard['EMV2-tc5'] = "4000000000001091";
# Unverified Payment Credential (Payment Authentication)
$testCard['DAF-tc1a'] = "4000090000000847";
?>