<?php

namespace Bytefusion\DeploymentBundle\Service;

/**
 * Class CloudflareService
 * @package Bytefusion\DeploymentBundle\Service
 */
class CloudflareService {

    /** @var string */
    private $cloudflare_url;
    /** @var string */
    private $cloudflare_key;
    /** @var string */
    private $cloudflare_email;

    /**
     * @param string $cloudflare_url
     * @param string $cloudflare_key
     * @param string $cloudflare_email
     */
    public function __construct($cloudflare_url, $cloudflare_key, $cloudflare_email)
    {
        $this->cloudflare_url = $cloudflare_url;
        $this->cloudflare_key = $cloudflare_key;
        $this->cloudflare_email = $cloudflare_email;
    }

    /**
     * @param $action
     * @param array $params
     * @return mixed
     */
    public function doRequest($action, array $params) {
        $params['a'] = $action;
        $params['tkn'] = $this->cloudflare_key;
        $params['email'] = $this->cloudflare_email;

        $ch = curl_init($this->cloudflare_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result);
    }

    /**
     * Returns list of domains on cloudflare account
     */
    public function getList() {
        return $this->doRequest('zone_load_multi', array());
    }

    public function clearCache($domain) {
        return $this->doRequest('fpurge_ts',
            array(
                'z' => $domain,
                'v' => 1
            )
        );
    }
}