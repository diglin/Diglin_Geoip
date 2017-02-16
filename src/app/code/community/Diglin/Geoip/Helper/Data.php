<?php
/**
 * Diglin GmbH - Switzerland
 *
 * @author      Sylvain Rayé <support at diglin.com>
 * @category    Diglin
 * @package     Diglin_Geoip
 * @copyright   Copyright (c) 2011-2017 Diglin (http://www.diglin.com)
 */

use GeoIp2\Database\Reader;

/**
 * Class Diglin_Geoip_Helper_Data
 */
class Diglin_Geoip_Helper_Data extends Mage_Core_Helper_Abstract
{
    const NOT_FOUND = '';

    const TYPE_FREE = 'free';
    const TYPE_PAID = 'paid';

    const TYPE_RESTR_STRICT = 1;
    const TYPE_RESTR_REDIRECT = 2;

    const GEOIP_LOG = 'diglin_geopip.log';

    const CFG_ENABLED               = 'diglin_geoip/general/active';
    const CFG_USER_AGENT           = 'diglin_geoip/general/user_agent';
    const CFG_DEFAULT_REDIRECT      = 'diglin_geoip/general/url_redirect';
    const CFG_MAXMIND_TYPE          = 'diglin_geoip/general/maxmind_type';
    const CFG_MAXMIND_USERID        = 'diglin_geoip/general/maxmind_userid';
    const CFG_MAXMIND_LICENSE       = 'diglin_geoip/general/maxmind_license';
    const CFG_TYPE                  = 'diglin_geoip/general/restriction_type';

    const CFG_COUNTRIES_ALLOWED     = 'diglin_geoip/country_restrictions/countries_allowed';
    const CFG_COUNTRIES_RESTRICTED  = 'diglin_geoip/country_restrictions/countries_restricted';

    const CFG_IPS_ALLOWED           = 'diglin_geoip/ip_restrictions/ips_allowed';
    const CFG_IPS_ALLOWED_FILENAME  = 'diglin_geoip/ip_restrictions/ips_allowed_filename';
    const CFG_IPS_RESTRICTED        = 'diglin_geoip/ip_restrictions/ips_restricted';
    const CFG_IPS_RESTRICTED_FILENAME = 'diglin_geoip/ip_restrictions/ips_restricted_filename';

    /**
     * @return mixed
     */
    public function isEnabled()
    {
        return Mage::getStoreConfig(self::CFG_ENABLED);
    }
    /**
     * @return mixed
     */
    public function getType()
    {
        return Mage::getStoreConfig(self::CFG_MAXMIND_TYPE);
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return Mage::getStoreConfig(self::CFG_MAXMIND_USERID);
    }

    /**
     * @return mixed
     */
    public function getLicense()
    {
        return Mage::getStoreConfig(self::CFG_MAXMIND_LICENSE);
    }

    /**
     * @return bool
     */
    public function isFreeLicense()
    {
        return (Mage::getStoreConfig(self::CFG_MAXMIND_TYPE) == self::TYPE_FREE);
    }

    /**
     * @return string
     */
    public function getVisitorIpAddress()
    {
        return Mage::helper('core/http')->getRemoteAddr();
    }

    /**
     * @return array
     */
    public function getUserGeolocByIpDatabaseFile()
    {
        $reader = new Reader(Mage::getBaseDir('media') . DS . 'geoip' . DS . 'GeoLite2-City.mmdb');

        $ip = $this->getVisitorIpAddress();

        if (empty($ip)) {
            return [];
        }

        $record = $reader->city($ip);

        return array(
            'iso_code'  => $record->country->isoCode,
            'latitude'  => $record->location->latitude,
            'longitude' => $record->location->longitude,
        );
    }

    /**
     * @return array
     */
    public function getUserGeolocByIpApiRequest()
    {
        $ipaddress = $this->getVisitorIpAddress();
        $geoloc = array(
            'iso_code'  => self::NOT_FOUND,
            'latitude'  => self::NOT_FOUND,
            'longitude' => self::NOT_FOUND,
        );

        if (!$ipaddress) {
            return $geoloc;
        }

        $query = 'https://geoip.maxmind.com/geoip/v2.1/city/' . $ipaddress;
        $opts = array(
            'http' => array(
                'method'  => "GET",
                'header'  => "Authorization: Basic " . base64_encode($this->getUserId() . ':' . $this->getLicense()) . "\r\n",
                'timeout' => 1
            )
        );

        $context = stream_context_create($opts);
        try {
            $file = file_get_contents($query, false, $context);
            if ($file !== false) {
                $result = Mage::helper('core')->jsonDecode($file);
                if (isset($result["country"]) && isset($result["country"]["iso_code"])) {
                    $geoloc = array(
                        'iso_code'  => $result["country"]["iso_code"],
                        'latitude'  => $result["location"]["latitude"],
                        'longitude' => $result["location"]["longitude"],
                    );
                }
            }
        } catch (Exception $e) {
            Mage::log($e, Zend_Log::ERR, self::GEOIP_LOG);
        }

        return $geoloc;
    }

    /**
     * @return mixed|string
     */
    public function getUserCountryCodeByIp()
    {
        // HTTP_CF_IPCOUNTRY = Cloudflare Server variable
        $geolocCountry = isset($_SERVER["HTTP_CF_IPCOUNTRY"]) ? $_SERVER["HTTP_CF_IPCOUNTRY"] : '';

        if (!$geolocCountry) {

            // Api Request
            if (!$this->isFreeLicense()) {
                $geoloc = $this->getUserGeolocByIpApiRequest();
            } else {
                $geoloc = $this->getUserGeolocByIpDatabaseFile();
            }

            // We set cookie for the shop, but we prefer to use session because when we set a cookie
            // $_COOKIE is not updated, and we cannot get cookie in footer for example.
            // It happen when you have 0 cookie and we don't need to redirect (IP FR and store eu_fr)

            // store.domain.com => .domain.com
            $hostArray = explode('.', Mage::app()->getRequest()->getHttpHost());
            array_shift($hostArray);
            $cookieHost = '.' . implode('.', $hostArray);

            Mage::getSingleton('core/cookie')
                ->set('geoloc', Mage::helper('core')->jsonEncode($geoloc), 3600 * 24, '/', $cookieHost, true, false);

            $geolocCountry = $geoloc['iso_code'];
        }

        return $geolocCountry;
    }

    /**
     * Return true if visitor is a bot
     *
     * @return bool
     */
    public function isBot()
    {
        $userAgent = Mage::helper('core/http')->getHttpUserAgent();
        return (isset($userAgent) && preg_match('/' . Mage::getStoreConfig(self::CFG_USER_AGENT) . '/i', $userAgent));
    }

    /**
     * @param string|null $url
     * @return string|null
     */
    public function getRedirectUrl($url = null)
    {
        $defaultUrl = Mage::getStoreConfig(self::CFG_DEFAULT_REDIRECT);

        if (empty($url) && !empty($defaultUrl)) {
            $url = $defaultUrl;
        }

        return str_replace('[[base_url]]', Mage::app()->getStore()->getBaseUrl(), $url);
    }

    /**
     * @return bool|int|null|string
     */
    public function getUserUrlRedirectionByIp()
    {
        $ip = $this->getVisitorIpAddress();

        $restrictedIps = $this->_getRestrictedIps();
        $allowedIps = $this->_getAllowedIps();

        $toRestrict = false;

        if (in_array($ip, $restrictedIps) && !in_array($ip, $allowedIps)) {
            $toRestrict = true;
        }

        return ($toRestrict) ? $this->_getRestrictionsUrl() : ((in_array($ip, $allowedIps)) ? true : false);
    }

    /**
     * @return array|mixed
     */
    protected function _getRestrictedIps()
    {
        $restrictedIps = explode("\n", Mage::getStoreConfig(self::CFG_IPS_RESTRICTED));
        $restrictedIpsFilename = Mage::getStoreConfig(self::CFG_IPS_RESTRICTED_FILENAME);

        $cacheKeyIpsRestricted = md5(sprintf('ips_restricted_filename_%s', $restrictedIpsFilename));
        $cacheData = Mage::app()->getCache()->load($cacheKeyIpsRestricted);

        if (!empty($restrictedIpsFilename) && empty($cacheData)) {
            $file = Mage::getBaseDir('media') . DS . ltrim($restrictedIpsFilename, '/');

            if (file_exists($file)) {
                $restrictedIpsContent = explode("\n", @file_get_contents($file));
                $restrictedIps = array_merge($restrictedIpsContent, $restrictedIps);
            }

            Mage::app()->getCache()->save(serialize($restrictedIps), $cacheKeyIpsRestricted, array(Mage_Core_Model_Config::CACHE_TAG));
        } else if (!empty($restrictedIpsFilename) && !empty($cacheData)) {
            $restrictedIps = unserialize($cacheData);
        }

        return (!empty($restrictedIps)) ? $restrictedIps : [];
    }

    /**
     * @return array
     */
    protected function _getAllowedIps()
    {
        $allowedIps = explode("\n", Mage::getStoreConfig(self::CFG_IPS_ALLOWED));
        $allowedIpsFilename = Mage::getStoreConfig(self::CFG_IPS_ALLOWED_FILENAME);

        $cacheKeyIpsAllowed = md5(sprintf('ips_allowed_filename_%s', $allowedIpsFilename));
        $cacheData = Mage::app()->getCache()->load($cacheKeyIpsAllowed);

        if (!empty($allowedIpsFilename) && empty($cacheData)) {
            $file = Mage::getBaseDir('media') . DS . ltrim($allowedIpsFilename, '/');

            if (file_exists($file)) {
                $allowedIpsContent = explode("\n", @file_get_contents($file));
                $allowedIps = array_merge($allowedIpsContent, $allowedIps);
            }

            Mage::app()->getCache()->save(serialize($allowedIps), $cacheKeyIpsAllowed, array(Mage_Core_Model_Config::CACHE_TAG));
        } else if (!empty($allowedIpsFilename) && !empty($cacheData)) {
            $allowedIps = unserialize($cacheData);
        }

        return (!empty($allowedIps)) ? $allowedIps : [];
    }

    /**
     * @return bool|int|null|string
     */
    public function getUserUrlRedirectionByCountry()
    {
        try {
            $countryVisitorCode = $this->getUserCountryCodeByIp();
        } catch (\Exception $e) {
            Mage::log($e->getMessage(), Zend_Log::INFO, self::GEOIP_LOG);
            return false;
        }

        $restrictedCountries = explode(',', Mage::getStoreConfig(self::CFG_COUNTRIES_RESTRICTED));
        $allowedCountries = explode(',', Mage::getStoreConfig(self::CFG_COUNTRIES_ALLOWED));

        $toRestrict = false;

        if (in_array($countryVisitorCode, $restrictedCountries) && !in_array($countryVisitorCode, $allowedCountries)) {
            $toRestrict = true;
        }

        return ($toRestrict) ? $this->_getRestrictionsUrl() : false;
    }

    /**
     * @return int|null|string
     */
    protected function _getRestrictionsUrl()
    {
        switch (Mage::getStoreConfig(self::CFG_TYPE)) {
            case self::TYPE_RESTR_REDIRECT:
                $url = $this->getRedirectUrl();
                break;
            case self::TYPE_RESTR_STRICT:
            default:
                $url = self::TYPE_RESTR_STRICT;
                break;
        }

        return $url;
    }
}