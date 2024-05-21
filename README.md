# OPSSHIELD module for WHMCS

This module allows resellers to automatically provision OPSSHIELD/cPGuard licenses directly within your WHMCS admin panel. It simplifies the process of selling and managing the licenses for your customers.


## Installation

Download the files from the repo and place them under `modules/servers/opsshield/`

```bash
  modules/servers/opsshield/
  |- opsshield.php
  |  lib/
  |  templates/
  .
  .
```
    
## Setting up

This guide walks you through the initial setup process for the OPSSHIELD WHMCS provisioning module. The first step involves adding a new server within your WHMCS admin panel to connect to the OPSSHIELD API.

## Step 1. Add a new server

First we have to add a new server in the WHMCS admin

1. Go to Configuration > System Settings > Servers and click on `+ Add new server` 
2. Select `OPSSHIELD Licenses` as the Module
3. Enter Hostname or IP Address as `manage.opsshield.com` (Unused, but required to be filled in by WHMCS)
4. Enter the email or client ID of your OPSSHIELD account as the Username (Doesn't matter as it is unused)
5. Enter your reseller API key obtained from OPSSHIELD as password
6. Click `Test Connection` to validate the connection
7. Enter a Server Name and Save the Server.

![image](https://github.com/opsshieldllp/whmcs-module/assets/81526091/7de917fd-9bf7-4e99-8142-2c96d87d6bec)

## Step 2. Create a welcome email template

Before we can proceed to create new Product/Service packages we have to create two Product email templates for the products

1. Go to Configuration > System Settings > Email Templates and click on `+ Create New Email Template`
2. Select "Email Type" as `Product/Service`, Enter `cPGuard welcome email` as "Unique Name" and click `Create`
3. In the new form, Enter the From address and other details as required
4. Enter `New cPGuard license created` as subject and enter the following in the content


```
Dear {$client_name},

Your order for {$service_product_name} has now been activated. Please keep this message for your records.

License key: {$service_domain}

Product/Service: {$service_product_name}
Payment Method: {$service_payment_method}
Amount: {$service_recurring_amount}
Billing Cycle: {$service_billing_cycle}
Next Due Date: {$service_next_due_date}

Installing cPGuard
For information on installing cPGuard on your server, visit the getting started guide

You can find answers to the most common questions in cPGuard Knowledge base.
If you have additional questions or concerns feel free to contact cPGuard support team.

Accessing UI
You can access the cPGuard UI by signing on to https://app.opsshield.com with your credentials
Check for an invitation email to create an account on OPSSHIELD if you haven't already created one.

Thank you for choosing us.

{$signature}
```
> [!NOTE]
> You should highlight heading texts like "Installing cPGuard" and "Accessing UI" bold in the email template editor.

## Step 3. Create an invitation email template

Next, we create a template used for sending an invitation link for creating an OPSSHIELD account for the client (if the client does not have one).
The client uses their OPSSHIELD account to access the cPGuard App Portal UI

1. Go to Configuration > System Settings > Email Templates and click on `+ Create New Email Template`
2. Select "Email Type" as `Product/Service`, Enter `cPGuard invitation email` as "Unique Name" and click `Create`
3. In the new form, Enter the From address and other details as required
4. Enter `Complete registration for new cPGuard service` as subject and enter the following in the content

```
Dear {$client_name},

Complete registration at OPSSHIELD

For accessing the cPGuard UI you have to create an account at OPSSHIELD
Please click on the invitation link below to fill out a form and create your OPSSHIELD account

{$invitation_link}

You can access your cPGuard dashboard by signing on to https://app.opsshield.com with your new credentials


Thank you for choosing us.

{$signature}
```

> [!NOTE]
> Invitation email is send to clients (identified by email) who does not already have an OPSSHIELD account. An OPSSHIELD account is required to login and view the cPGuard UI at https://app.opsshield.com

## Step 4. Adding a Product/Service

1. Go to Configuration > System Settings > Products/Services and click on `+ Create a New Product`
2. Select `Other` for product type and Choose/Create a product group as you prefer
3. Enter a product name. Eg. 'cPGuard unlimited accounts' or 'cPGuard 50 user accounts pack'
4. Select `OPSSHIELD Licenses` as the module and click `Continue`

![image](https://github.com/opsshieldllp/whmcs-module/assets/81526091/9398d303-7f2a-4a0f-8af5-f1b16e2e0b1d)


5. On the "Details" tab, select the Welcome Email template created earlier
7. On the "Module Settings" tab, Select the respective reseller package available for you
8. Select the Currency and Invitation email template created earlier and choose desired Auto-setup option
9. Set your Pricing on the "Pricing" Tab and click 'Save Changes'

![image](https://github.com/opsshieldllp/whmcs-module/assets/81526091/90c41572-e55a-4c31-bb58-7ac517f8ec21)


Go ahead and try placing a test order to check everything is working


## Using the Upgrade/Downgrade option from WHMCS

You can opt to use the Upgrade/Downgrade option in WHMCS by choosing the upgrade packages in the "Upgrades" tab of the product edit form (Configuration > System Settings > Products/Services > Edit the desired product)

Ugrade/downgrade option works by cancelling the existing license at OPSSHIELD and issuing a new license and updating the respective service in WHMCS. Refund will be calculated and credited to you reseller account if there are no open invoices for the respective license (i.e. only if more than 5 days to renewal). 

> [!NOTE]
> A new license key will be generated which must be applied on the client server to use the new package.

You should create an Upgrade email template to send the license key and alert the user of the license key change.

#### Sample email content

```
Dear {$client_name},

Your service change was successful

Please apply the key {$service_domain} on server at the earliest
Use the following command to apply the license on your server

cpgcli license --key {$service_domain}

The old license is cancelled and should be immideately replaced with the new key.
You can read more at https://opsshield.com/help/cpguard/manage-license/

Thank you for choosing us.

{$signature}
```

### Sending notification for low credit balance

WHMCS Admin users will be notified by email of low credit balance in your reseller account at OPSSHIELD when the credit balance falls below 250 USD/INR. You set a custom limit by modifying and setting the following string as `Access Hash` in the server edit form (Configuration > System Settings > Servers > Edit server)

```
notify_low_credit = 100
```

![image](https://github.com/opsshieldllp/whmcs-module/assets/81526091/a30c5623-936c-42e1-b9b9-5156b054c367)


The check happens along with the server meta refresh API
