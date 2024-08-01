<?php

namespace WHMCS\Module\Server\OpsShield;

class API
{
    private $endpoint = 'https://manage.opsshield.com/plugin/reseller_api/cpguard/';
    public $api_key;

    public function __construct($api_key)
    {
        $this->api_key = $api_key;
    }

    public function accountDetails()
    {
        return $this->request('accountdetails');
    }

    public function getPackages()
    {
        return $this->request('getpackages');

    }

    function getPricingId($package_id, $currency)
    {

        $pricings = $this->request('getpackagepricing', ['package_id' => $package_id]);

        foreach ($pricings as $pricing) {
            if ($pricing['currency'] == $currency) {
                return $pricing['id'];
            }
        }
        throw new \Exception("matching package not found");
    }

    public function addLicense($pricing_id, $quantity, $client_email)
    {
        $data = [
            'pricing_id' => $pricing_id,
            'quantity' => $quantity,
            'client_email' => $client_email
        ];

        return $this->request('addlicense', $data);
    }

    public function suspendLicense($service_id)
    {
        return $this->request('suspendlicense', ['service_id' => $service_id]);
    }

    public function unSuspendLicense($service_id)
    {
        return $this->request('unsuspendlicense', ['service_id' => $service_id]);
    }

    public function changePackage($service_id, $pricing_id, $quantity, $client_email)
    {
        //Cancels existing and creates a new license
        $data = [
            'service_id' => $service_id,
            'pricing_id' => $pricing_id,
            'quantity' => $quantity,
            'client_email' => $client_email
        ];

        return $this->request('changepackage', $data);
    }

    public function cancelLicense($service_id)
    {
        return $this->request('cancellicense', ['service_id' => $service_id]);
    }

    public function reissueLicense($service_id)
    {
        return $this->request('reissuelicense', ['service_id' => $service_id]);
    }

    public function getLicenseDetails($service_id)
    {
        return $this->request('getlicense', ['service_id' => $service_id]);
    }

    public function invitationLink($email)
    {
        return $this->request('invitationlink', ['client_email' => $email]);
    }

    public function listLicenses($status = 'all')
    {
        return $this->request('listlicenses', ['status' => $status]);
    }

    public function testConnection()
    {

        $ch = curl_init($this->endpoint . 'getcredit');

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['apikey' => $this->api_key]);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent' => 'cPGuardWHMCS',
            'Accept' => 'application/json',
        ]);

        // Receive server response ...
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error_no = curl_errno($ch);
        if ($error_no) {
            return "API failed; Curl error : #$error_no" . curl_error($ch);
        }

        curl_close($ch);

        if ($httpcode == 200) {
            return true;
        } else {
            $response = json_decode($response, true);
            return $response['message'] ?? 'Connection failed';
        }

    }

    public function request($method, $data = [])
    {

        $data['apikey'] = $this->api_key;

        $ch = curl_init($this->endpoint . $method);

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent' => 'cPGuardWHMCS',
            'Accept' => 'application/json',
        ]);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error_no = curl_errno($ch);
        if ($error_no) {
            throw new \Exception("API failed; Curl error : #$error_no" . curl_error($ch));
        }

        curl_close($ch);

        if ($httpcode !== 200) {
            throw new \Exception("API failed; Response : $response");
        }

        return json_decode($response, true);

    }

}


