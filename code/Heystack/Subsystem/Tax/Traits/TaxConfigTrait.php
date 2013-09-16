<?php
/**
 * This file is part of the Ecommerce-Tax package
 *
 * @package Ecommerce-Tax
 */

/**
 * Traits namespace
 */
namespace Heystack\Subsystem\Tax\Traits;

use Heystack\Subsystem\Core\Exception\ConfigurationException;

/**
 * Provides an implementation of setting and getting the configuration for use on a TaxHandler class
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Payment
 */
trait TaxConfigTrait
{
    public static $inclusiveTaxType = 'Inclusive';
    public static $exclusiveTaxType = 'Exclusive';

    /**
     * Sets an array of config parameters onto the data array.
     * Checks to see if the configuration array is well formed.
     * @param  array      $config
     * @throws \Exception
     */
    public function setConfig(array $config)
    {
        foreach ($config as $key => $value) {

            $key = strtoupper($key);

            if (is_array($value) && isset($value['Rate']) && isset($value['Type'])) {

                if (in_array($value['Type'], array(self::$inclusiveTaxType, self::$exclusiveTaxType))) {

                    if (is_numeric($value['Rate'])) {

                        $this->data[self::CONFIG_KEY][$key] = $value;

                    } else {
                        throw new ConfigurationException('Tax ' . $key . ' should have a numeric `Rate`');
                    }

                } else {
                    throw new ConfigurationException('Tax ' . $key . ' should have a `Type` of `Exclusive` or `Inclusive`');
                }

            } else {
                throw new ConfigurationException('Tax ' . $key . ' should have an array with `Rate` & `Type` keys');
            }

        }
    }

    /**
     * Retrieves the configuration array
     * @return array
     */
    public function getConfig()
    {
        return isset($this->data[self::CONFIG_KEY]) ? $this->data[self::CONFIG_KEY] : null;
    }

}
