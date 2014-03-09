<?php

namespace Heystack\Tax\Interfaces;

use Heystack\Ecommerce\Transaction\Interfaces\TransactionModifierInterface;

/**
 * Interface TaxHandlerInterface
 * @package Heystack\Tax\Interfaces
 */
interface TaxHandlerInterface extends TransactionModifierInterface
{
    /**
     * @return mixed
     */
    public function updateTotal();

    /**
     * Sets an array of config parameters onto the data array.
     * Checks to see if the configuration array is well formed.
     * @param array $config
     * @return void
     * @throws \Heystack\Core\Exception\ConfigurationException
     */
    public function setConfig(array $config);

    /**
     * @return mixed
     */
    public function getConfig();
}
