# Data Exchange API (DX API) Sample Repository

This repository provides sample implementation and usage examples of the Data Exchange API (DX API). The DX API enables secure data exchange between systems, giving merchants real-time insights and additional data during transaction processing. Merchants can use this API to gain visibility into issuer behavior and optimize their authentication strategies.

## Table of Contents

- [Endpoints](#endpoints)
- [Encryption Guidelines](#encryption-guidelines)
- [Sample Request](#sample-request)
- [Sample Response](#sample-response)
- [Error Scenarios](#error-scenarios)
- [Usage](#usage)

---

## Endpoints

**Staging URL**: `https://dataexchangestag.cardinalcommerce.com`

**Production URL**: `https://dataexchange.cardinalcommerce.com`

### Resource Paths

- **Unencrypted**: `/V1/AccountNumber/GetInfo`
- **Encrypted**: `/V1/AccountNumber/EncryptedGetInfo`

### Header Requirements

- For encrypted requests, set the `Content-Type` header to `application/jose`.

---

## Encryption Guidelines

### Encryption Keypair

- **Algorithm**: RSA 2048 bits
- **Signature Algorithm**: SHA256WITHRSA

### JSON Web Encryption (JWE) Creation

- **Algorithm**: `RSA-OAEP`
- **Method**: `A256GCM`
- Include the `kid` header provided by Cardinal/Visa.

### Example JWE Header (Decoded)

```json
{
  "alg": "RSA-OAEP-256",
  "enc": "A128CBC-HS256",
  "kid": "dataexchange-pke-mpi-cc-2048sha2-stag-2023"
}
```

---

## Sample Request

### Unencrypted Example

```json
{
  "Signature": "KmL2SLBeTRRU9TlxA6XfnAYg5yWn1QwEO0GL1RtP8mg=",
  "Timestamp": "2024-02-21T20:10:20.872Z",
  "Identifier": "59c282d02f3e7357b4aa6f13",
  "Algorithm": "SHA-256",
  "OrgUnitId": "59c2745f2f3e7357b4aa516a",
  "Payload": {
    "AccountNumber": "400009******0800",
    "AcquirerCountryCode": "840"
  }
}
```

### Encrypted Example

```plaintext
eyJraWQiOiJkYXRhZXhjaGFuZ2UtcGtlLW1waS1jYy0yMDQ4c2hhMi1wcm9kLTIwMjMiLCJlbmMiOiJBMjU2R0NNIiwiYWxnIjoiUlNBLU9BRVAifQ.tmCD4Euz5gl64AjrX8vULyg4_YRJSu0vbKDCHq-1MJ3uhtikIxU5_TuQ4muFW2APXq7xbvBdmNulIZg0zEpTSZrMD6rcpXkO4b0vCvNz-WIrL6D2rOxD82rmJRnKktgHdi1-AKeBil9SVV1sqfVXGgJ0EFyuMP38TK8pQW5PKIcHt_KyiIj2AeCt6hR2yc83ZkWR_IHj4EMC-xT2PNyVOu7rXDTW6F-SlHqWWIQ5DaIvk-N7LCoO4o-TGHn0-mUKti42H_jMUixpb9tPf514PH3mhOxlqr2kzTrn43aQO2z17TSyoeZmsLtnx1nNY9uMUbqF7sl2YreKFHCQROr8-w.4TUjSAhAwk4kxz7l
```

---

## Sample Response

```json
{
  "ErrorNumber": 0,
  "ErrorDescription": "Success",
  "RequestId": "b3933183-48df-409f-94ff-12952364009b",
  "Payload": {
    "Account": {
      "CardBrand": "Visa",
      "LastFour": "0094"
    },
    "Issuer": {
      "SupportedVersions": [
        {
          "Version": "2.1.0",
          "Capabilities": ["AuthenticationAvailableAtACS", "DAF"],
          "MethodURLPresent": true
        },
        {
          "Version": "2.2.0",
          "Capabilities": [
            "AuthenticationAvailableAtACS",
            "DecoupledAuthentication",
            "DataOnly",
            "DelegatedAuthentication",
            "IssuerTRA",
            "DAF"
          ],
          "MethodURLPresent": true
        }
      ]
    },
    "ReferenceId": "51ca6679-12ed-47c4-8982-1a29e10d4587"
  }
}
```

---

## Error Scenarios

| HTTPS Status | Error Number | Description                                                                                       |
| ------------ | ------------ | ------------------------------------------------------------------------------------------------- |
| 200          | 1010         | Invalid Signature. Your request contains an invalid signature.                                    |
| 200          | 1011         | Signature expired. Your signature is not within the acceptable time frame.                        |
| 200          | 2000         | AccountNumber is not valid. Check that the account number is the correct length and valid format. |
| 400          | N/A          | Invalid OrgUnitId or JSON.                                                                        |
| 415          | N/A          | Invalid Content-Type in the header. Should be `application/json` for unencrypted endpoint.        |

---

## Usage

1. Clone the repository:
   ```bash
   git clone https://github.com/jaechow/cardinal.git
   ```
2. Review and update configurations for API integration in `data/config.php`.
3. Navigate to the project directory:
   ```bash
   cd cardinal/cruise/dx
   ```
4. Run the sample scripts to test API interactions:
   ```bash
   php -S localhost:3000
   ```
5. Navigate browser to http://localhost:3000/dx.php

---
