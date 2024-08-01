<?php

use WHMCS\Service\Status;
use WHMCS\Service\Service;
use WHMCS\User\User;
use WHMCS\Module\Server\OpsShield\API;
use WHMCS\Module\Server\OpsShield\ServiceModel;

/**
 * WHMCS Module for OPSSHIELD Licenses
 *
 * cPGuard is a comprehensive security suite that can help to protect your 
 * web server from a variety of threats. It offers a variety of features to
 * safeguard against malware, hacking attempts and other threats.
 *
 * This module allows resellers to automatically provision OPSSHIELD/cPGuard
 * licenses directly within your WHMCS admin panel. It simplifies the
 * process of selling and managing the licenses for your customers.
 *
 *
 * For more information, please refer to the online documentation.
 *
 * @see https://github.com/opsshieldllp/whmcs-module/blob/main/README.md
 *
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Define module related meta data.
 *
 * Values returned here are used to determine module related abilities and
 * settings.
 *
 * @see https://developers.whmcs.com/provisioning-modules/meta-data-params/
 *
 * @return array
 */
function opsshield_MetaData()
{
    return array(
        'DisplayName' => 'OPSSHIELD Licenses',
        'APIVersion' => '1.1', // Use API Version 1.1
        'RequiresServer' => true, // Set true if module requires a server to work
        'DefaultNonSSLPort' => '443', // Default Non-SSL Connection Port
        'DefaultSSLPort' => '443', // Default SSL Connection Port
        'ServiceSingleSignOnLabel' => 'Login to Panel as User',
        'AdminSingleSignOnLabel' => 'Manage account',

        // The display name of the unique identifier to be displayed on the table output
        'ListAccountsUniqueIdentifierDisplayName' => 'Domain',
        // The field in the return that matches the unique identifier
        'ListAccountsUniqueIdentifierField' => 'domain',
        // The config option indexed field from the _ConfigOptions function that identifies the product on the remote system
        'ListAccountsProductField' => 'configoption1',
    );
}


function opsshield_AutoPopulateServerConfig()
{
    return array(
        "name" => "OPSSHIELD Reseller",
        "hostname" => 'manage.opsshield.com',
    );
}

/**
 * Define product configuration options.
 *
 * The values you return here define the configuration options that are
 * presented to a user when configuring a product for use with the module. These
 * values are then made available in all module function calls with the key name
 * configoptionX - with X being the index number of the field from 1 to 24.
 *
 * Examples of each and their possible configuration parameters are provided in
 * this sample function.
 *
 * @see https://developers.whmcs.com/provisioning-modules/config-options/
 *
 * @return array
 */
function opsshield_ConfigOptions($params)
{
    $email_templates = [];
    $result = localAPI('GetEmailTemplates', ['type' => 'product']);
    foreach ($result['emailtemplates']['emailtemplate'] as $template) {
        if ($template['custom']) {
            $email_templates[$template['name']] = $template['name'];
        }
    }

    if (empty($email_templates)) {
        $email_templates[] = "Please create a template as per documentation";
    }

    return array(

        "Product" => [
            "FriendlyName" => "Reseller Package",
            "Type" => "dropdown",
            "Loader" => "opsshield_getPackages",
            "SimpleMode" => true,
        ],
        'Currency' => array(
            "FriendlyName" => "Pricing Currency",
            'Type' => 'dropdown',
            'Options' => array(
                'USD' => 'USD',
                'INR' => 'INR',
            ),
            'Description' => '*Subject to availabitiy in your reseller package',
            "SimpleMode" => true,
        ),
        'EmailTemplate' => array(
            "FriendlyName" => "Invitation email template",
            'Type' => 'dropdown',
            'Options' => $email_templates,
            "SimpleMode" => true,
        )
    );
}

/**
 * get Packages from OPSSHIELD.
 *
 * @return array
 */
function opsshield_getPackages(array $params)
{
    $api = new API($params['serverpassword']);
    $packages = $api->getPackages();

    $package_select = [];

    foreach ($packages as $package) {
        $package_select[$package['id']] = $package['name'];// . '<input type = "hidden" value="' . base64_encode(json_encode($package)) . '"/>';
    }

    return $package_select;
}

/**
 * Provision a new instance of a product/service.
 *
 * Attempt to provision a new instance of a given product/service. This is
 * called any time provisioning is requested inside of WHMCS. Depending upon the
 * configuration, this can be any of:
 * * When a new order is placed
 * * When an invoice for a new order is paid
 * * Upon manual request by an admin user
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return string "success" or an error message
 */
function opsshield_CreateAccount(array $params)
{
    try {

        ServiceModel::setupDatabase();

        $service = ServiceModel::find($params['serviceid']);
        $api = new API($params['serverpassword']);

        if ($service) {
            $response = $api->getLicenseDetails($service->service_id);
            if ($response['status'] != 'canceled') {
                throw new Exception('Service already created.');
            }
        }

        $package_id = $params['configoption1'];
        if (empty($package_id)) {
            throw new Exception("Product package is not set in module");
        }

        $currency = in_array($params['configoption2'], ['USD', 'INR']) ? $params['configoption2'] : 'USD';

        $pricing_id = $api->getPricingId($package_id, $currency);

        $response = $api->addLicense($pricing_id, 1, $params['clientsdetails']['email']);

        if (isset($response['services'])) {
            //Success: License created
            $params['model']->serviceProperties->save(['Domain' => $response['services'][0]['license_key']]);

            $service = $service ?? new ServiceModel;
            $service->id = $params['serviceid'];
            $service->serverid = $params['serverid'];
            $service->userid = $params['userid'];

            $service->service_id = $response['services'][0]['service_id'];
            $service->license_key = $response['services'][0]['license_key'];
            $service->status = $response['services'][0]['status'];

            $service->save();

            if ($response['invite_link']) {
                $email_data = array(
                    'messagename' => $params['configoption3'],
                    'id' => $params['serviceid'],
                    'customvars' => base64_encode(
                        serialize(
                            array(
                                "invitation_link" => $response['invite_link']
                            )
                        )
                    ),
                );
                localAPI('SendEmail', $email_data);
            }

        } else {
            throw new Exception('API to OPSSHIELD failed!');
        }


    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'opsshield',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

/**
 * Suspend an instance of a product/service.
 *
 * Called when a suspension is requested. This is invoked automatically by WHMCS
 * when a product becomes overdue on payment or can be called manually by admin
 * user.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return string "success" or an error message
 */
function opsshield_SuspendAccount(array $params)
{
    try {

        $service = ServiceModel::find($params['serviceid']);
        if ($service) {

            $api = new API($params['serverpassword']);

            $response = $api->suspendLicense($service->service_id);

            if (isset($response['status'])) {

                if ($response['status']) {
                    $service->status = 'suspended';
                    $service->save();
                } else {
                    $service->status = 'cancelled';
                    $service->save();
                    throw new Exception('Suspend failed! Service is not active.');
                }

            } else {
                throw new Exception('API to OPSSHIELD failed!');
            }
        } else {
            throw new Exception('Service does not exist.');
        }

    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'opsshield',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

/**
 * Un-suspend instance of a product/service.
 *
 * Called when an un-suspension is requested. This is invoked
 * automatically upon payment of an overdue invoice for a product, or
 * can be called manually by admin user.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return string "success" or an error message
 */
function opsshield_UnsuspendAccount(array $params)
{
    try {

        $service = ServiceModel::find($params['serviceid']);
        if ($service) {

            $api = new API($params['serverpassword']);
            $response = $api->unSuspendLicense($service->service_id);

            if (isset($response['status'])) {

                if ($response['status']) {
                    $service->status = 'active';
                    $service->save();
                } else {
                    $service->status = 'cancelled';
                    $service->save();
                    throw new Exception('Unsuspend failed! Service is cancelled.');
                }

            } else {
                throw new Exception('API to OPSSHIELD failed!');
            }
        } else {
            throw new Exception('Service does not exist.');
        }

    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'opsshield',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

/**
 * Upgrade/Downgrade a package.
 *
 * This function runs for upgrading and downgrading of products.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return string "success" or an error message
 */
function opsshield_ChangePackage(array $params)
{
    try {

        $service = ServiceModel::find($params['serviceid']);
        if ($service) {

            //Now create a new license 
            $package_id = $params['configoption1'];
            if (empty($package_id)) {
                throw new Exception("Product package is not set in module");
            }

            //First cancel existing license
            $api = new API($params['serverpassword']);

            $currency = in_array($params['configoption2'], ['USD', 'INR']) ? $params['configoption2'] : 'USD';
            $pricing_id = $api->getPricingId($package_id, $currency);

            $response = $api->changePackage($service->service_id, $pricing_id, 1, $params['clientsdetails']['email']);

            if (isset($response['services'])) {
                //Success: new license created
                $params['model']->serviceProperties->save(['Domain' => $response['services'][0]['license_key']]);

                $service->service_id = $response['services'][0]['service_id'];
                $service->license_key = $response['services'][0]['license_key'];
                $service->status = $response['services'][0]['status'];
                $service->details = [];
                $service->sync_time = null;

                $service->save();

            } else if (isset($response['error'])) {
                throw new Exception($response['error']);
            } else {
                throw new Exception('API to OPSSHIELD failed!');
            }

        } else {
            throw new Exception('Service does not exist.');
        }
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'opsshield',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

/**
 * Terminate instance of a product/service.
 *
 * Called when a termination is requested. This can be invoked automatically for
 * overdue products if enabled, or requested manually by an admin user.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return string "success" or an error message
 */
function opsshield_TerminateAccount(array $params)
{
    try {
        $service = ServiceModel::find($params['serviceid']);
        if ($service) {

            $api = new API($params['serverpassword']);

            $response = $api->cancelLicense($service->service_id);

            if (isset($response['status'])) {
                $service->status = 'cancelled';
                $service->save();
            } else {
                throw new Exception('API to OPSSHIELD failed!');
            }

        } else {
            throw new Exception('Service does not exist.');
        }
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'opsshield',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

/**
 * Test connection with the given server parameters.
 *
 * Allows an admin user to verify that an API connection can be
 * successfully made with the given configuration parameters for a
 * server.
 *
 * When defined in a module, a Test Connection button will appear
 * alongside the Server Type dropdown when adding or editing an
 * existing server.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return array
 */
function opsshield_TestConnection(array $params)
{
    try {
        // Call the service's connection test function.
        $api = new API($params['serverpassword']);
        $response = $api->testConnection();

        if ($response === true) {
            $success = true;
            $errorMsg = '';
        } else {
            $success = false;
            $errorMsg = $response;
        }
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'opsshield',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        $success = false;
        $errorMsg = $e->getMessage();
    }

    return array(
        'success' => $success,
        'error' => $errorMsg,
    );
}

/**
 * Additional actions an admin user can invoke.
 *
 * Define additional actions that an admin user can perform for an
 * instance of a product/service.
 *
 * @see opsshield_buttonOneFunction()
 *
 * @return array
 */
function opsshield_AdminCustomButtonArray()
{
    return array(
        "Reissue" => "reissueService",
        "Resend Invitation" => "resendInvitation"
    );
}

/**
 * Additional actions a client user can invoke.
 *
 * Define additional actions a client user can perform for an instance of a
 * product/service.
 *
 * Any actions you define here will be automatically displayed in the available
 * list of actions within the client area.
 *
 * @return array
 */
function opsshield_ClientAreaCustomButtonArray($params)
{
    return array(
        "Reissue" => "reissueService"
    );
}

/**
 * Custom function for performing an additional action.
 *
 * You can define an unlimited number of custom functions in this way.
 *
 * Similar to all other module call functions, they should either return
 * 'success' or an error message to be displayed.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 * @see opsshield_AdminCustomButtonArray()
 *
 * @return string "success" or an error message
 */
function opsshield_reissueService(array $params)
{
    try {

        $service = ServiceModel::find($params['serviceid']);
        if ($service) {

            $api = new API($params['serverpassword']);
            $response = $api->reissueLicense($service->service_id);
            if (!isset($response['status'])) {
                throw new Exception('API to OPSSHIELD failed!');
            }

        } else {
            throw new Exception('Service does not exist.');
        }

    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'opsshield',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

/**
 * Custom function for performing an additional action.
 *
 * You can define an unlimited number of custom functions in this way.
 *
 * Similar to all other module call functions, they should either return
 * 'success' or an error message to be displayed.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 * @see opsshield_AdminCustomButtonArray()
 *
 * @return string "success" or an error message
 */
function opsshield_resendInvitation(array $params)
{
    try {

        $service = ServiceModel::find($params['serviceid']);
        if ($service) {

            $api = new API($params['serverpassword']);
            $response = $api->invitationLink($params['clientsdetails']['email']);

            if (isset($response['client']) || isset($response['invite_link'])) {
                if (empty($response['invite_link'])) {
                    throw new Exception('OPSSHIELD account already created!');
                } else {
                    $email_data = array(
                        'messagename' => $params['configoption3'],
                        'id' => $params['serviceid'],
                        'customvars' => base64_encode(
                            serialize(
                                array(
                                    "invitation_link" => $response['invite_link']
                                )
                            )
                        ),
                    );
                    localAPI('SendEmail', $email_data);
                }

            } else {
                throw new Exception('API to OPSSHIELD failed!');
            }

        } else {
            throw new Exception('Service does not exist.');
        }

    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'opsshield',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

/**
 * Admin services tab additional fields.
 *
 * Define additional rows and fields to be displayed in the admin area service
 * information and management page within the clients profile.
 *
 * Supports an unlimited number of additional field labels and content of any
 * type to output.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 * @see opsshield_AdminServicesTabFieldsSave()
 *
 * @return array
 */
function opsshield_AdminServicesTabFields(array $params)
{
    try {
        // Call the service's function, using the values provided by WHMCS in
        // `$params`.
        $response = array();

        $api = new API($params['serverpassword']);

        $service = ServiceModel::find($params['serviceid']);
        if ($service) {

            $response = $api->getLicenseDetails($service->service_id);
            if (isset($response['license_key'])) {
                $service->status = $response['status'];
                $service->details = $response;
                $service->sync_time = date('Y-m-d H:i:s');
                $service->save();

                $params['model']->serviceProperties->save(['Dedicated IP' => $response['ips'][0]]);
            }

            return array(
                'License Key' => $service->license_key,
                'IP Address' => implode(', ', $service->details['ips'] ?? []),
                'Hostname' => implode(', ', $service->details['domains'] ?? []),
                'Package' => "#" . $service->details['package_id'] . " " . $service->details['package_name'],
                'Status at OPSSHIELD' => $service->status,
                'Reissue' => ($service->details['reissue'] ? 'yes' : 'no')
            );

            if (!isset($response['license_key'])) {
                return ['OPSSHIELD API:' => "Error obtaining status"];
            }

        } else {
            return ['OPSSHIELD API:' => "Service not found in module table"];
        }

    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'opsshield',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        // In an error condition, simply return no additional fields to display.
    }

    return array();
}

function opsshield_ListAccounts(array $params)
{
    try {
        // Call the remote api to obtain the list of accounts. Use the values provided by
        // WHMCS in `$params`. (https://developers.whmcs.com/provisioning-modules/module-parameters/)
        //$data = mymodule_call($params, 'listaccounts');

        ServiceModel::setupDatabase();

        $api = new API($params['serverpassword']);
        $response = $api->listLicenses('all');

        if (isset($response['services'])) {

            $accounts = [];
            $status = [
                'active' => Status::ACTIVE,
                'suspended' => Status::SUSPENDED,
                'canceled' => Status::TERMINATED,
                'pending' => Status::PENDING
            ];

            $whm_services = Service::where('server', $params['serverid'])->get();
            foreach ($whm_services as $whm_service) {

                $service = ServiceModel::find($whm_service->id);
                $service_id = $service->service_id;
                $license = current(array_filter($response['services'], function ($array) use ($service_id) {
                    return $array['service_id'] == $service_id;
                }));

                if (empty($license)) {
                    continue;
                }

                $whm_user = User::find($whm_service->userid);

                $accounts[] = [
                    // The remote accounts email address
                    'email' => $whm_user->email,
                    // The remote accounts username
                    'username' => '',
                    // The remote accounts primary domain name
                    'domain' => $license['license_key'],
                    // This can be one of the above fields or something different.
                    // In this example, the unique identifier is the domain name
                    'uniqueIdentifier' => $license['license_key'],
                    // The accounts package on the remote server
                    'product' => $license['package_id'],
                    // The remote accounts primary IP Address
                    'primaryip' => $license['ips'][0] ?? '',
                    // The remote accounts creation date (Format: Y-m-d H:i:s)
                    'created' => $license['date_added'],
                    // The remote accounts status (Status::ACTIVE or Status::SUSPENDED)
                    'status' => $status[$license['status'] ?? Status::TERMINATED],
                ];
            }

            return [
                'success' => true,
                'accounts' => $accounts,
            ];

        } else {
            throw new Exception('API to OPSSHIELD failed!');
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
}

function opsshield_GetRemoteMetaData(array $params)
{
    try {
        $api = new API($params['serverpassword']);
        $details = $api->accountDetails();
        if (isset($details['due'])) {

            $extra_conf = parse_ini_string($params['serveraccesshash']);
            $credit_warning = $extra_conf['notify_low_credit'] ?? 250;

            $balance = $dues = [];
            $low_balance = false;

            foreach ($details['credit'] as $currency => $amount) {
                if ($amount > 0) {
                    $balance[] = "$amount $currency";
                    if ($amount < $credit_warning) {
                        $low_balance = true;
                    }
                }
            }

            if (empty($balance)) { //both balance zero
                $low_balance = true;
                $balance[] = "0";
            }

            foreach ($details['due'] as $currency => $amount) {
                if ($amount > 0) {
                    $dues[] = "$amount $currency";
                }
            }
            $dues = empty($dues) ? ["0"] : $dues;

            //Send low balance email
            $hours = explode(',', str_replace(' ', '', $extra_conf['notify_hours'] ?? "*"));
            if ($low_balance && $credit_warning > 0 && (in_array('*', $hours) || in_array(date('H'), $hours))) {
                $results = localAPI('SendAdminEmail', [
                    'customsubject' => 'Credit balance low at OPSSHIELD',
                    'custommessage' => "<p>Hello Admin,</p><h3>You have low balance in your OPSSHIELD reseller account</h3>
                                        <p>Please add some credits to your account at the earliest.</p>
                                        <h5>Balance : " . implode(', ', $balance) . "</h5><h5>Dues : " . implode(', ', $dues) . "</h5>",
                ]);
            }

            return $details;
        } else {
            throw new Exception('API to OPSSHIELD failed!');
        }
    } catch (Exception $e) {
        return array("success" => false, "error" => $e->getMessage());
    }
}

function opsshield_RenderRemoteMetaData(array $params)
{
    $remoteData = $params["remoteData"];
    if ($remoteData) {

        $metaData = $remoteData->metaData;

        $credits = $dues = [];

        foreach ($metaData['credit'] as $currency => $amount) {
            if ($amount > 0) {
                $credits[] = "$amount $currency";
            }
        }
        $credits = empty($credits) ? [0] : $credits;
        foreach ($metaData['due'] as $currency => $amount) {
            if ($amount > 0) {
                $dues[] = "$amount $currency";
            }
        }
        $dues = empty($dues) ? [0] : $dues;

        $active_count = $metaData['services']['active'];
        $suspended_count = $metaData['services']['suspended'];

        return "Credit Balance: " . implode(', ', $credits) . "<br>\nAmount Due: " . implode(', ', $dues) . "<br>
                Active services: $active_count <br>\nSuspended services: $suspended_count";
    }
    return "";
}


/**
 * Perform single sign-on for a server.
 *
 * Called when single sign-on is requested for a server assigned to the module.
 *
 * This differs from ServiceSingleSignOn in that it relates to a server
 * instance within the admin area, as opposed to a single client instance of a
 * product/service.
 *
 * When successful, returns a URL to which the user should be redirected to.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return array
 */
function opsshield_AdminSingleSignOn(array $params)
{
    try {
        return array(
            'success' => true,
            'redirectTo' => 'https://manage.opsshield.com/client',
        );
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'opsshield',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return array(
            'success' => false,
            'errorMsg' => $e->getMessage(),
        );
    }
}

/**
 * Client area output logic handling.
 *
 * This function is used to define module specific client area output. It should
 * return an array consisting of a template file and optional additional
 * template variables to make available to that template.
 *
 * The template file you return can be one of two types:
 *
 * * tabOverviewModuleOutputTemplate - The output of the template provided here
 *   will be displayed as part of the default product/service client area
 *   product overview page.
 *
 * * tabOverviewReplacementTemplate - Alternatively using this option allows you
 *   to entirely take control of the product/service overview page within the
 *   client area.
 *
 * Whichever option you choose, extra template variables are defined in the same
 * way. This demonstrates the use of the full replacement.
 *
 * Please Note: Using tabOverviewReplacementTemplate means you should display
 * the standard information such as pricing and billing details in your custom
 * template or they will not be visible to the end user.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return array
 */
function opsshield_ClientArea(array $params)
{
    try {

        $service = ServiceModel::find($params['serviceid']);
        if ($service) {

            if (1 || empty($service->details) || strtotime($service->sync_time) < time() - 180) {
                $api = new API($params['serverpassword']);
                $response = $api->getLicenseDetails($service->service_id);
                if (isset($response['license_key'])) {
                    $service->status = $response['status'];
                    $service->details = $response;
                    $service->sync_time = date('Y-m-d H:i:s');
                    $service->save();
                }
            }

            return array(
                'tabOverviewReplacementTemplate' => 'templates/overview.tpl',
                'templateVariables' => array(
                    'license_key' => $service->license_key,
                    'ips' => implode(', ', $service->details['ips'] ?? []),
                    'hostnames' => implode(', ', $service->details['domains'] ?? []),
                    'install_command' => $service->details['commands']['install'] ?? '',
                    'apply_command' => $service->details['commands']['apply'] ?? '',
                    'reissue' => $service->details['reissue'] ?? null,
                ),
            );
        }



    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'opsshield',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        // In an error condition, display an error page.
        return array(
            'tabOverviewReplacementTemplate' => 'error.tpl',
            'templateVariables' => array(
                'usefulErrorHelper' => $e->getMessage(),
            ),
        );
    }
}


