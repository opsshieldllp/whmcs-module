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
    
## Setting up Server and Product/Service in WHMCS admin

After installation of the provision module, we can add a server in WHMCS admin and then create Products/Service packages

### 1. Add a server

1. Go to Configuration > System Settings > Servers and click on `+ Add new server`
2. Select `OPSSHIELD Licenses` as the Module
3. Enter Hostname or IP Address as `manage.opsshield.com` (Unused, but required to be filled in by WHMCS)
4. Enter the email or client ID of your OPSSHIELD account as the Username (Doesn't matter as it is unused)
5. Enter your reseller API key obtained from OPSSHIELD as password
6. Click `Test Connection` to validate the connection
7. Enter a Server Name and Save the Server.


### 2. Create email templates

Before we can proceed to create new Product/Service packages we have to create two Product email templates for the products. You may modify the email content as required.

#### 2.1 Create invitation email template

1. Go to Configuration > System Settings > Email Templates and click on `+ Create New Email Template`
2. Select "Email Type" as `Product/Service`, Enter `cPGuard invitation email` as "Unique Name" and click `Create`
3. Enter `Complete registration for new cPGuard service` as subject and enter the following in the content


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

#### 2.2 Create welcome email template

Before we can proceed to create new Product/Service packages we have to create two Product email templates for the products

1. Go to Configuration > System Settings > Email Templates and click on `+ Create New Email Template`
2. Select "Email Type" as `Product/Service`, Enter `cPGuard welcome email` as "Unique Name" and click `Create`
3. Enter `New cPGuard license created` as subject and enter the following in the content


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


### 3. Creating a product

1. Go to Configuration > System Settings > Products/Services and click on `+ Create a New Product`
2. Select `Other` for product type and Choose/Create a product group as you prefer
3. Enter a product name. Eg. 'cPGuard unlimited accounts' or 'cPGuard 50 user accounts pack'
4. Select `OPSSHIELD Licenses` as the module and click `Continue`
5. On the "Details" tab, select the Welcome Email template created earlier
7. On the "Module Settings" tab, Select the respective reseller package available for you
8. Select the Currency and Invitation email template created earlier and choose desired Auto-setup option
9. Set your Pricing on the "Pricing" Tab and click 'Save Changes'

Go ahead and try placing a test order to check everything is working



