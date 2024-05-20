<?php

namespace WHMCS\Module\Server\OpsShield;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;

class API
{
    private $endpoint = 'https://manage.opsshield.com/plugin/reseller_api/cpguard/';
    public $api_key;
    private $client;

    public function __construct($api_key)
    {
        $this->api_key = $api_key;

        $this->client = new Client([
            'base_uri' => $this->endpoint,
            RequestOptions::CONNECT_TIMEOUT => 30,
            RequestOptions::HTTP_ERRORS => false,
            RequestOptions::DEBUG => false,
            RequestOptions::HEADERS => [
                'User-Agent' => 'cPGuardWHMCS',
                'Accept' => 'application/json',
            ],
        ]);


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
        $request = $this->client->post('getcredit', [
            'form_params' => ['apikey' => $this->api_key],
        ]);

        if ($request->getStatusCode() === 200) {
            return true;
        } else {
            $response = json_decode($request->getBody()->getContents(), true);
            return $response['message'] ?? 'Connection failed';
        }

    }

    public function request($method, $data = [])
    {
        $data = array_merge(['apikey' => $this->api_key], $data);

        $request = $this->client->post($method, [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'form_params' => $data,
        ]);

        if ($request->getStatusCode() !== 200) {
            throw new \Exception("API failed; Response : " . $request->getBody()->getContents());
        }

        $response = $request->getBody()->getContents();

        return json_decode($response, true);

    }

}


