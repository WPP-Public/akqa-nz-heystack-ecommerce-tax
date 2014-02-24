<?php

namespace Heystack\Tax\Traits;

use Heystack\Tax\Interfaces\TaxHandlerInterface;

/**
 * Class HasTaxHandlerTrait
 * @package Heystack\Tax\Traits
 */
trait HasTaxHandlerTrait
{
    /**
     * @var \Heystack\Tax\Interfaces\TaxHandlerInterface
     */
    protected $taxHandler;
    
    /**
     * @return \Heystack\Tax\Interfaces\TaxHandlerInterface
     */
    public function getTaxHandler()
    {
        return $this->taxHandler;
    }

    /**
     * @param \Heystack\Tax\Interfaces\TaxHandlerInterface $taxHandler
     * @return mixed
     */
    public function setTaxHandler(TaxHandlerInterface $taxHandler)
    {
        $this->taxHandler = $taxHandler;
    }
} 