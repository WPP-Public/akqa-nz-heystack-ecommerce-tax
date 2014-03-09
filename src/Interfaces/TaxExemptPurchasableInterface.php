<?php

namespace Heystack\Tax\Interfaces;

use Heystack\Ecommerce\Purchasable\Interfaces\PurchasableInterface;

/**
 * Interface TaxExemptPurchasableInterface
 * @package Heystack\Tax\Interfaces
 */
interface TaxExemptPurchasableInterface extends PurchasableInterface
{
    /**
     * Returns whether the purchasable has tax exemption
     * @return bool
     */
    public function isTaxExempt();
} 