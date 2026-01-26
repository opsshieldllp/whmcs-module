# Reseller API Documentation

## Authentication

The API requires an API key associated with a reseller account on [https://manage.opsshield.com](https://manage.opsshield.com). All requests must be made over HTTPS.

---

## Get Account Details

Check your reseller account credit balance and due amount.

**Method:** POST
**URL:** `https://manage.opsshield.com/plugin/reseller_api/cpguard/accountdetails`

### Request Body

| Field | Type | Description |
| --- | --- | --- |
| `apikey` | string | Your reseller account API key |

### Response

```json
{
    "credit": {
        "USD": "5.4000",
        "INR": "0"
    },
    "due": {
        "USD": "0",
        "INR": "0"
    },
    "services": {
        "active": 54,
        "suspended": 8,
        "canceled": 12
    }
}

```

---

## Get Packages

Fetch available packages and their pricing options. You may have access to multiple packages which can be used.
A package can have multiple pricing options, for example, the price can be lower when you pay yearly for the same service.

**Method:** POST
**URL:** `https://manage.opsshield.com/plugin/reseller_api/cpguard/getpackages`

### Request Body

| Field | Type | Description |
| --- | --- | --- |
| `apikey` | string | Your reseller account API key |

### Response

```json
[
    {
        "id": "5525",
        "name": "Reseller Unlimited",
        "description": "",
        "qty": null,
        "pricing": [
            {
                "id": "43",
                "term": "1",
                "period": "month",
                "price": "5.0000",
                "currency": "USD"
            }
        ]
    },
    {
        "id": "5526",
        "name": "Reseller Standard",
        "description": "",
        "qty": null,
        "pricing": [
            {
                "id": "44",
                "term": "1",
                "period": "month",
                "price": "3.5000",
                "currency": "USD"
            }
        ]
    }
]

```

> **Note:** `pricing_id` may be cached/stored locally. It is optional to pull package/pricing details each time you want to create a service as it only changes if we update or create a package/pricing for you.

---

## Get Package Pricing

Get a list of pricing options for a package.

**Method:** POST
**URL:** `https://manage.opsshield.com/plugin/reseller_api/cpguard/getpackagepricing`

### Request Body

| Field | Type | Description |
| --- | --- | --- |
| `apikey` | string | Your reseller account API key |
| `package_id` | integer | The id of the specific package to get its pricing schemes |

### Response

```json
[
    {
        "id": "558",
        "term": "1",
        "period": "month",
        "price": "5.0000",
        "currency": "USD"
    },
    {
        "id": "559",
        "term": "6",
        "period": "month",
        "price": "5.0000",
        "currency": "USD"
    }
]

```

---

## Create a new license/service

Create a new license with a chosen pricing scheme.

**Method:** POST
**URL:** `https://manage.opsshield.com/plugin/reseller_api/cpguard/addlicense`

### Request Body

| Field | Type | Description |
| --- | --- | --- |
| `apikey` | string | Your reseller account API key |
| `pricing_id` | integer | ID of the package pricing scheme |
| `quantity` | integer | No of licenses |
| `client_email` | email | Email address of the buyer (For providing UI access. For self use send your registered email) |

### Response

```json
{
    "invoice_id": "351254",
    "services": [
        {
            "service_id": "501224",
            "license_key": "8rs-rsxxxxxxxxxxxxxxx",
            "pricing_id": "558",
            "status": "active",
            "date_added": "2023-05-24 10:29:04",
            "date_renews": "2023-06-24 10:29:04"
        },
        {
            "service_id": "501225",
            "license_key": "8rs-rsxxxxxxxxxxxxxxx",
            "pricing_id": "558",
            "status": "active",
            "date_added": "2023-05-24 10:29:04",
            "date_renews": "2023-06-24 10:29:04"
        }
    ],
    "client_id": "1202",
    "Invite_link": null
}

```

> **Note:** If the buyer(identified by the provided email) does not have an opsshield account, an invite link will be provided in the above API.
> The invite link should be passed to the client in an email or via your UI.
> They will have to complete registration and log into our app portal to view the cPGuard UI for the server.

---

## Get License Details

Get details of a single license/service.

**Method:** POST
**URL:** `https://manage.opsshield.com/plugin/reseller_api/cpguard/getlicense`

### Request Body

*You need to send either `service_id` or `license_key` in a single request.*

| Field | Type | Description |
| --- | --- | --- |
| `apikey` | string | Your reseller account API key |
| `service_id` | integer | The id of the specific package to get its pricing schemes |
| `license_key` | string | License key of service |

### Response

```json
{
    "service_id": 35,
    "pricing_id": 35,
    "status": "canceled",
    "date_added": "2024-05-01 15:17:30",
    "date_renews": "2024-06-01 15:17:30",
    "date_last_renewed": null,
    "date_suspended": null,
    "date_canceled": "2024-06-01 15:17:30",
    "package_id": 16,
    "package_name": "Reseller Standard Gold",
    "domains": [],
    "ips": [],
    "license_key": "4rs-rvda15jegesipkrc",
    "reissue": true,
    "commands": {
        "install": "cd /usr/local/src && rm -f cpguard_install.sh && curl -o cpguard_install.sh -L https://downloads.opsshield.com/cpguard/cpguard_install.sh && bash cpguard_install.sh 4rs-rvda15jegesipkrc",
        "apply": "cpgcli license --key 4rs-rvda15jegesipkrc"
    }
}

```

---

## Generate Invitation Link

Get details of a single license/service.

**Method:** POST
**URL:** `https://manage.opsshield.com/plugin/reseller_api/cpguard/invitationlink`

### Request Body

| Field | Type | Description |
| --- | --- | --- |
| `apikey` | string | Your reseller account API key |
| `client_email` | email | Email address of the customer |

### Response

```json
{
    "client": NULL,
    "invite_link": "https://manage.opsshield.com/client/authorize/signup?sid=a0927a12878ac652b2ba463784cebd63"
}

```

The invitation link will be returned if the client registration process is incomplete.

---

## List licenses/services

List all licenses under your reseller account.

**Method:** POST
**URL:** `https://manage.opsshield.com/plugin/reseller_api/cpguard/addlicense`

### Request Body

| Field | Type | Description |
| --- | --- | --- |
| `apikey` | string | Your reseller account API key |
| `status` | integer | (optional) License status: "all, active, canceled, suspended, in_review, scheduled_cancellation" |
| `page` | integer | (optional) page number to list page-wise |

### Response

```json
{
    "services": [
        {
            "service_id": "501224",
            "pricing_id": "558",
            "status": "active",
            "date_added": "2023-05-24 12:25:44",
            "date_renews": "2023-06-24 12:25:44",
            "date_last_renewed": null,
            "date_suspended": null,
            "date_canceled": null,
            "package_id": "233",
            "package_name": "Reseller special package",
            "domains": [
                "da.myserver.com"
            ],
            "ips": [
                "157.000.000.12"
            ],
            "license_key": "8rs-rsxxxxxxxxxxxxxxxxx",
            "reissue": false
        },
        {
            "service_id": "501225",
            "pricing_id": "558",
            "status": "active",
            "date_added": "2023-05-24 10:29:04",
            "date_renews": "2023-06-24 10:29:04",
            "date_last_renewed": null,
            "date_suspended": null,
            "date_canceled": null,
            "package_id": "233",
            "package_name": "Reseller special package",
            "domains": [],
            "ips": [],
            "license_key": "8rs-rsxxxxxxxxxxxxxxxxx",
            "reissue": true
        }
    ]
}

```

---

## Reissue/Update License

Reissue the license for moving it to a different server or IP

**Method:** POST
**URL:** `https://manage.opsshield.com/plugin/reseller_api/cpguard/reissuelicense`

### Request Body

| Field | Type | Description |
| --- | --- | --- |
| `apikey` | string | Your reseller account API key |
| `service_id` | integer | Id of service |

### Response

```json
{
    "status": true,
    "service_id": "501224"
}

```

> **Note:** Status will be false on failure

---

## Change Package

Reissue the license for moving it to a different server or IP

**Method:** POST
**URL:** `https://manage.opsshield.com/plugin/reseller_api/cpguard/changePackage`

### Request Body

| Field | Type | Description |
| --- | --- | --- |
| `apikey` | string | Your reseller account API key |
| `service_id` | integer | Id of existing service |
| `pricing_id` | integer | Target pricing id |
| `client_email` | email | Clients email address |

### Response

```json
{
    "invoice_id": "4123481",
    "services": [
        {
            "service_id": 193424,
            "license_key": "8rs-rsjssxxxxxxxxxxxxxxxxxxxx",
            "pricing_id": 90,
            "status": "active",
            "date_added": "2024-07-06 06:06:05",
            "date_renews": "2024-08-06 06:06:05"
        }
    ],
    "client": 41753,
    "invite_link": null
}

```

> **Note:** This works by cancelling the old license key and creating a new one. Hence client has to apply the new license key on their server.

---

## Suspend service/license

Suspend a single license.

**Method:** POST
**URL:** `https://manage.opsshield.com/plugin/reseller_api/cpguard/suspendlicense`

### Request Body

| Field | Type | Description |
| --- | --- | --- |
| `apikey` | string | Your reseller account API key |
| `service_id` | integer | Id of service |

### Response

```json
{
    "status": true,
    "service_id": "501224"
}

```

> **Note:** Status will be false on failure

---

## Unsuspend service/license

Unsuspend a single license.

**Method:** POST
**URL:** `https://manage.opsshield.com/plugin/reseller_api/cpguard/unsuspendlicense`

### Request Body

| Field | Type | Description |
| --- | --- | --- |
| `apikey` | string | Your reseller account API key |
| `service_id` | integer | Id of service |

### Response

```json
{
    "status": true,
    "service_id": "501224"
}

```

> **Note:** Status will be false on failure

---

## Cancel service/license

Cancel a single license. Active licenses will be automatically renewed on the renewal date using the credit amount.
Make sure unwanted licenses are cancelled in time to avoid being charged.

**Method:** POST
**URL:** `https://manage.opsshield.com/plugin/reseller_api/cpguard/cancellicense`

### Request Body

| Field | Type | Description |
| --- | --- | --- |
| `apikey` | string | Your reseller account API key |
| `service_id` | integer | Id of service |

### Response

```json
{
    "status": true,
    "service_id": "501224"
}

```

> **Note:** Status will be false on failure

---

## Delete service/license

Delete a single license. Only cancelled licenses can be deleted.

**Method:** POST
**URL:** `https://manage.opsshield.com/plugin/reseller_api/cpguard/deletelicense`

### Request Body

| Field | Type | Description |
| --- | --- | --- |
| `apikey` | string | Your reseller account API key |
| `service_id` | integer | Id of service |

### Response

```json
{
    "status": true,
    "service_id": "501224"
}

```

> **Note:** Status will be false on failure. Active licenses have to be first cancelled to be deleted.

---

## Errors

Here are several types of errors that may be encountered when working with the API:

1. Not using https or not using the API key will give.

```json
HTTP/1.1 403 Forbidden
{
    "error": "HTTP/1.1 403 Forbidden",
    "message": "The requested resource is not accessible."
}

```

2. Providing invalid credentials will result in a 401 Unauthorized response.

```json
HTTP/1.1 401 Unauthorized
{
    "error": "HTTP/1.1 401 Unauthorized",
    "message": "The authorization details given appear to be invalid."
}

```

3. Missing parameters will result in a 400 Bad Request response.

```json
HTTP/1.1 400 Bad Request
{
    "error": "HTTP/1.1 400 Bad Request",
    "message": "The request cannot be fulfilled due to bad syntax.",
    "field": {
        "service_id": "service_id required."
    }
}

```

4. Sending invalid parameters will result in a 417 Expectation Failed response.

```json
HTTP/1.1 417 Expectation Failed
{
    "error": "HTTP/1.1 417 Expectation Failed",
    "message": "Value of parameter(s) is invalid"
}

```

5. If an unexpected error occurs a 500 Internal Server Error will result. You may need to contact cPGuard support for assistance.

```json
HTTP/1.1 500 Internal Server Error
{
    "error": "HTTP/1.1 500 Internal Server Error",
    "message": "An unexpected error occured."
}

```

6. When the API is under maintenance mode, it will return a 503 Service Unavailable response.

```json
HTTP/1.1 503 Service Unavailable
{
    "error": "HTTP/1.1 503 Service Unavailable",
    "message": "The requested resource is currently unavailable due to maintenance."
}

```
