<?php

namespace Heystack\Subsystem\Tax\Interfaces;

use Heystack\Subsystem\Ecommerce\Transaction\Interfaces\TransactionModifierInterface;

interface TaxHandlerInterface extends TransactionModifierInterface
{
    public function updateTotal();
    public function setConfig(array $config);
    public function getConfig();
}
