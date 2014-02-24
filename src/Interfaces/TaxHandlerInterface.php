<?php

namespace Heystack\Tax\Interfaces;

use Heystack\Ecommerce\Transaction\Interfaces\TransactionModifierInterface;

interface TaxHandlerInterface extends TransactionModifierInterface
{
    public function updateTotal();
    public function setConfig(array $config);
    public function getConfig();
}
