<?php

namespace Heystack\Subsystem\Tax\Traits;

use Heystack\Subsystem\Tax\Interfaces\TaxHandlerInterface;

/**
 * Class HasTaxHandlerTrait
 * @package Heystack\Subsystem\Tax\Traits
 */
trait HasTaxHandlerTrait
{
    /**
     * @var \Heystack\Subsystem\Tax\Interfaces\TaxHandlerInterface
     */
    protected $taxHandler;
    
    /**
     * @return \Heystack\Subsystem\Tax\Interfaces\TaxHandlerInterface
     */
    public function getTaxHandler()
    {
        return $this->taxHandler;
    }

    /**
     * @param \Heystack\Subsystem\Tax\Interfaces\TaxHandlerInterface $taxHandler
     * @return mixed
     */
    public function setTaxHandler(TaxHandlerInterface $taxHandler)
    {
        $this->taxHandler = $taxHandler;
    }
} 