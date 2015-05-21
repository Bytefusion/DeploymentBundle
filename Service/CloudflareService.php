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
     * Basic request function
     *
     * @param string $action
     * @param array $params
     * @return mixed
     */
    protected function doRequest($action, array $params) {
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
     * 3.1 - "stats" - Retrieve domain statistics for a given time frame
     *
     * @param string $domain The zone (domain) that statistics are being retrieved from
     * @param int $interval The time interval for the statistics denoted by these values:
     * For these values, the latest data is from one day ago
     * <ul>
     * <li>20 = Past 30 days</li>
     * <li>30 = Past 7 days</li>
     * <li>40 = Past day</li>
     * </ul>
     * The following values are for Pro accounts
     * <ul>
     * <li>100 = 24 hours ago</li>
     * <li>110 = 12 hours ago</li>
     * <li>120 = 6 hours ago</li>
     * @return mixed
     */
    public function getStats($domain, $interval) {
        return $this->doRequest('stats', array(
            'z'         => $domain,
            'interval'  => $interval
        ));
    }

    /**
     * 3.2 - "zone_load_multi" - Retrieve the list of domains
     *
     * This lists all domains in a CloudFlare account along with other data.
     *
     * @return mixed
     */
    public function getList() {
        return $this->doRequest('zone_load_multi', array());
    }

    /**
     * 3.3 - "rec_load_all" - Retrieve DNS Records of a given domain
     *
     * @param string $domain The domain that records are being retrieved from
     * @param int|bool $offset Optional.
     * If the has_more parameter in the JSON response is true, you can use o=N
     * to offset the starting position for the response.
     * By default, this call will list the first 180 records for a zone.
     * @return mixed
     */
    public function getDnsEntries($domain, $offset = false) {
        if($offset) {
            return $this->doRequest('rec_load_all', array(
                'z' => $domain,
                'o' => $offset
            ));
        } else {
            return $this->doRequest('rec_load_all', array(
                'z' => $domain
            ));
        }
    }

    /**
     * 3.4 - "zone_check" - Checks for active zones and returns their corresponding zids
     *
     * @param array $zones array of zones
     * @return mixed
     */
    public function getZoneIds(array $zones) {
        return $this->doRequest('zone_check', array(
            'zones' => implode(',', $zones)
        ));
    }

    /**
     * 3.6 - "ip_lkup" - Check threat score for a given IP
     *
     * Find the current threat score for a given IP.
     * Note that scores are on a logarithmic scale, where a higher score indicates a higher threat.
     *
     * @param string $ip The target IP
     * @return mixed
     */
    public function getThreatScoreForIp($ip) {
        return $this->doRequest('ip_lkup', array(
            'ip' => $ip
        ));
    }

    /**
     * 3.7 - "zone_settings" - List all current setting values
     *
     * Retrieves all current settings for a given domain.
     *
     * @param string $domain The target domain
     * @return mixed
     */
    public function getSettings($domain) {
        return $this->doRequest('zone_settings', array(
            'z' => $domain
        ));
    }

    /**
     * 4.1 - "sec_lvl" - Set the security level
     *
     * This function sets the Basic Security Level to
     * I'M UNDER ATTACK! / HIGH / MEDIUM / LOW / ESSENTIALLY OFF.
     *
     * @param string $domain The target domain
     * @param string $security_level The security level:
     * <ul>
     * <li>"help" -- I'm under attack!</li>
     * <li>"high" -- High</li>
     * <li>"med" -- Medium</li>
     * <li>"low" -- Low</li>
     * <li>"eoff" -- Essentially Off</li>
     * </ul>
     * @return mixed
     */
    public function setSecurityLevel($domain, $security_level) {
        return $this->doRequest('sec_lvl', array(
            'z' => $domain,
            'v' => $security_level
        ));
    }

    /**
     * 4.2 - "cache_lvl" - Set the cache level
     *
     * This function sets the Caching Level to Aggressive or Basic.
     *
     * @param string $domain The target domain
     * @param string $cache_level
     * <ul>
     * <li>"agg" -- Aggressive</li>
     * <li>"basic" -- Basic</li>
     * </ul>
     * @return mixed
     */
    public function setCacheLevel($domain, $cache_level) {
        return $this->doRequest('cache_lvl', array(
            'z' => $domain,
            'v' => $cache_level
        ));
    }

    /**
     * 4.3 - "devmode" - Toggling Development Mode
     *
     * This function allows you to toggle Development Mode on or off for a particular domain.
     * When Development Mode is on the cache is bypassed.
     * Development mode remains on for 3 hours or until when it is toggled back off.
     *
     * @param string $domain The target domain
     * @param bool $dev_mode true for on, false for off
     * @return mixed
     */
    public function setDevMode($domain, $dev_mode = false) {
        return $this->doRequest('devmode', array(
            'z' => $domain,
            'v' => $dev_mode ? '1' : '0'
        ));
    }

    /**
     * 4.4 - "fpurge_ts" -- Clear CloudFlare's cache
     *
     * This function will purge CloudFlare of any cached files.
     * It may take up to 48 hours for the cache to rebuild and optimum performance
     * to be achieved so this function should be used sparingly.
     *
     * @param string $domain The target domain
     * @return mixed
     */
    public function clearCache($domain) {
        return $this->doRequest('fpurge_ts',
            array(
                'z' => $domain,
                'v' => 1
            )
        );
    }

    /**
     * 4.5 - "zone_file_purge" -- Purge a single file in CloudFlare's cache
     *
     * This function will purge a single file from CloudFlare's cache.
     *
     * @param string $domain The target domain
     * @param string $file_url
     * The full URL of the file that needs to be purged from CloudFlare's cache.
     * Keep in mind, that if an HTTP and an HTTPS version of the file exists,
     * then both versions will need to be purged independently
     * @return mixed
     */
    public function clearFileCache($domain, $file_url) {
        return $this->doRequest('zone_file_purge', array(
            'z'     => $domain,
            'url'   => $file_url
        ));
    }

    /**
     * 4.6 - Whitelist IP
     *
     * @param string $ip The IP address you want to whitelist
     * @return mixed
     */
    public function whitelistIp($ip) {
        return $this->doRequest('wl', array(
            'key' => $ip
        ));
    }

    /**
     * 4.6 - Blacklist IP
     *
     * @param string $ip The IP address you want to blacklist
     * @return mixed
     */
    public function blacklistIp($ip) {
        return $this->doRequest('ban', array(
            'key' => $ip
        ));
    }

    /**
     * 4.6 - Unlist IP
     *
     * @param string $ip The IP address you want to unlist
     * @return mixed
     */
    public function unlistIp($ip) {
        return $this->doRequest('null', array(
            'key' => $ip
        ));
    }

    /**
     * 4.7 - "ipv46" -- Set IPv6 support
     *
     * Toggles IPv6 support
     *
     * @param string $domain The target domain
     * @param bool $ipv6 true to enable, false to disable
     * @return mixed
     */
    public function setIPv6Enabled($domain, $ipv6 = true) {
        return $this->doRequest('ipv46', array(
            'z' => $domain,
            'v' => $ipv6 ? '3' : '0'
        ));
    }

    /**
     * 4.8 - "async" -- Set Rocket Loader
     *
     * Changes Rocket Loader setting
     *
     * @param string $domain The target domain
     * @param string $mode [0 = off, a = automatic, m = manual]
     * @return mixed
     */
    public function setRocketLoaderMode($domain, $mode) {
        return $this->doRequest('async', array(
            'z' => $domain,
            'v' => $mode
        ));
    }

    /**
     * 4.9 - "minify" -- Set Minification
     *
     * Changes minification settings
     *
     * @param string $domain The target domain
     * @param int $mode
     * <ul>
     * <li> 0 = off</li>
     * <li> 1 = JavaScript only</li>
     * <li> 2 = CSS only</li>
     * <li> 3 = JavaScript and CSS</li>
     * <li> 4 = HTML only</li>
     * <li> 5 = JavaScript and HTML</li>
     * <li> 6 = CSS and HTML</li>
     * <li> 7 = CSS, JavaScript, and HTML</li>
     * </ul>
     * @return mixed
     */
    public function setMinification($domain, $mode) {
        return $this->doRequest('minify', array(
            'z' => $domain,
            'v' => $mode
        ));
    }

    /**
     * 4.10 - "mirage2" -- Set Mirage2
     *
     * Sets mirage2 support
     *
     * @param string $domain The target domain
     * @param bool $enabled true to enable Mirage2, false to disable
     * @return mixed
     */
    public function setMirageEnabled($domain, $enabled = true) {
        return $this->doRequest('mirage2', array(
            'z' => $domain,
            'v' => $enabled ? '1' : '0'
        ));
    }

    /**
     * 5.1 - "rec_new" -- Add a DNS record
     *
     * Create a DNS record for a zone
     *
     * @param string $domain The target domain
     * @param string $type Type of DNS record. Values include: [A/CNAME/MX/TXT/SPF/AAAA/NS/SRV/LOC]
     * @param string $name Name of the DNS record.
     * @param string $content The content of the DNS record, will depend on the the type of record being added
     * @param int $ttl TTL of record in seconds. 1 = Automatic, otherwise, value must in between 120 and 86400 seconds.
     * @param string|bool $prio [applies to MX/SRV] MX record priority.
     * @param string|bool $service [applies to SRV] Service for SRV record
     * @param string|bool $srvname [applies to SRV] Service Name for SRV record
     * @param string|bool $protocol [applies to SRV] Protocol for SRV record. Values include: [_tcp/_udp/_tls].
     * @param string|bool $weight [applies to SRV] Weight for SRV record.
     * @param string|bool $port [applies to SRV] Port for SRV record
     * @param string|bool $target [applies SRV] Target for SRV record
     * @return mixed
     */
    public function addDnsRecord(
        $domain, $type, $name, $content, $ttl, $prio = false,
        $service = false, $srvname = false, $protocol = false,
        $weight = false, $port = false, $target = false
    ) {
        $params = array(
            'z'         => $domain,
            'type'      => $type,
            'name'      => $name,
            'content'   => $content,
            'ttl'       => $ttl
        );
        if($prio)       $params['prio']     = $prio;
        if($service)    $params['service']  = $service;
        if($srvname)    $params['srvname']  = $srvname;
        if($protocol)   $params['protocol'] = $protocol;
        if($weight)     $params['weight']   = $weight;
        if($port)       $params['port']     = $port;
        if($target)     $params['target']   = $target;

        return $this->doRequest('rec_new', $params);
    }

    /**
     * 5.2 - "rec_edit" -- Edit a DNS record
     *
     * Edit a DNS record for a zone. The record will be updated to the data passed through arguments here.
     *
     * @param string $domain The target domain
     * @param string $type Type of DNS record. Values include: [A/CNAME/MX/TXT/SPF/AAAA/NS/SRV/LOC]
     * @param string $id DNS Record ID. Available by using getDnsEntries().
     * @param string $name Name of the DNS record.
     * @param string $content The content of the DNS record, will depend on the the type of record being added
     * @param int $ttl TTL of record in seconds. 1 = Automatic, otherwise, value must in between 120 and 86400 seconds.
     * @param string|bool $prio [applies to MX/SRV] MX record priority.
     * @param string|bool $service [applies to SRV] Service for SRV record
     * @param string|bool $srvname [applies to SRV] Service Name for SRV record
     * @param string|bool $protocol [applies to SRV] Protocol for SRV record. Values include: [_tcp/_udp/_tls].
     * @param string|bool $weight [applies to SRV] Weight for SRV record.
     * @param string|bool $port [applies to SRV] Port for SRV record
     * @param string|bool $target [applies SRV] Target for SRV record
     * @return mixed
     */
    public function editDnsRecord(
        $domain, $type, $id, $name, $content, $ttl, $prio = false,
        $service = false, $srvname = false, $protocol = false,
        $weight = false, $port = false, $target = false
    ) {
        $params = array(
            'z'         => $domain,
            'type'      => $type,
            'id'        => $id,
            'name'      => $name,
            'content'   => $content,
            'ttl'       => $ttl
        );
        if($prio)       $params['prio']     = $prio;
        if($service)    $params['service']  = $service;
        if($srvname)    $params['srvname']  = $srvname;
        if($protocol)   $params['protocol'] = $protocol;
        if($weight)     $params['weight']   = $weight;
        if($port)       $params['port']     = $port;
        if($target)     $params['target']   = $target;

        return $this->doRequest('rec_edit', $params);
    }

    /**
     * 5.3 - "rec_delete" -- Delete a DNS record
     *
     * Delete a record for a domain.
     *
     * @param string $domain The target domain
     * @param string $id DNS Record ID. Available by using getDnsEntries().
     * @return mixed
     */
    public function deleteDnsRecord($domain, $id) {
        return $this->doRequest('rec_delete', array(
            'z'     => $domain,
            'id'    => $id
        ));
    }
}