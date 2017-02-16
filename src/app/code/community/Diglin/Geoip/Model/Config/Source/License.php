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
 * Class Diglin_Geoip_Model_Config_Source_License
 */
class Diglin_Geoip_Model_Config_Source_License
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $return = array();
        $options = $this->toArray();
        foreach ($options as $key => $option) {
            $return[] = array('value' => $key, 'label' => $option);
        }

        return $return;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $helper = Mage::helper('diglin_geoip');

        return array(
            Diglin_Geoip_Helper_Data::TYPE_FREE => $helper->__('Maxmind GeoLite2 Free'),
            Diglin_Geoip_Helper_Data::TYPE_PAID => $helper->__('Maxmind GeoLite2 Paid'),
        );
    }
}
