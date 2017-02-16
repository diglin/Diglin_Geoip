<?php
/**
 * Diglin GmbH - Switzerland
 *
 * @author      Sylvain Rayé <support at diglin.com>
 * @category    Diglin
 * @package     Diglin_Geoip
 * @copyright   Copyright (c) 2011-2017 Diglin (http://www.diglin.com)
 */

/**
 * Class Diglin_Geoip_Model_Observer
 */
class Diglin_Geoip_Model_Observer
{
    const CONFIG_PATH_PSR0NAMESPACES = 'global/psr0_namespaces';

    static $shouldAdd = true;

    /**
     * Get Namespaces To Register
     *
     * @return array
     */
    protected function _getNamespacesToRegister()
    {
        $namespaces = array();
        $node = Mage::getConfig()->getNode(self::CONFIG_PATH_PSR0NAMESPACES);
        if ($node && is_array($node->asArray())) {
            $namespaces = array_keys($node->asArray());
        }

        return $namespaces;
    }

    /**
     * Add PSR-0 Autoloader for our Diglin Geoip2 library
     *
     * Event
     * - resource_get_tablename
     * - add_spl_autoloader
     *
     * @return $this
     */
    public function addAutoloader()
    {
        if (!self::$shouldAdd) {
            return $this;
        }

        foreach ($this->_getNamespacesToRegister() as $namespace) {
            $namespace = str_replace('_', '/', $namespace);
            if (is_dir(Mage::getBaseDir('lib') . DS . $namespace)) {
                $args = array($namespace, Mage::getBaseDir('lib') . DS . $namespace);
                $autoloader = Mage::getModel("diglin_geoip/splAutoloader", $args);
                $autoloader->register();
            }
        }

        self::$shouldAdd = false;

        return $this;
    }

    /**
     * Event:
     * - controller_action_predispatch
     *
     * @param Varien_Event_Observer $observer
     */
    public function controllerActionPredispatch(Varien_Event_Observer $observer)
    {
        $helper = Mage::helper('diglin_geoip');

        if (!$helper->isEnabled()) {
            return;
        }

        $redirection = $helper->getUserUrlRedirectionByIp();

        if (!$redirection) {
            $redirection = $helper->getUserUrlRedirectionByCountry();
        }

        if (!$redirection || $helper->isBot()) {
            return;
        }

        if ($redirection == Diglin_Geoip_Helper_Data::TYPE_RESTR_STRICT) {
            Mage::app()->getResponse()
                ->setHttpResponseCode(403)
                ->sendHeaders()
            ;
        } else {
            Mage::app()->getResponse()
                ->setRedirect($redirection, 302)
                ->sendHeaders()
            ;
        }

        exit(0);
    }
}