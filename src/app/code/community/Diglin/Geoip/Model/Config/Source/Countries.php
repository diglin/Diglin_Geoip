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
 * Class Diglin_Geoip_Model_Config_Source_Countries
 */
class Diglin_Geoip_Model_Config_Source_Countries
{
    /**
     * @return mixed
     */
    public function toOptionArray()
    {
        return Mage::getModel('directory/country')->getCollection()->toOptionArray(false);
    }
}