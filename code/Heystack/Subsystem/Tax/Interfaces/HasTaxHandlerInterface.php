<?php

namespace Heystack\Subsystem\Tax\Interfaces;

/**
 * Interface HasTaxHandlerInterface
 * @package Heystack\Subsystem\Tax\Interfaces
 */
interface HasTaxHandlerInterface
{
    /**
     * @return mixed
     */
    public function getTaxHandler();

    /**
     * @param TaxHandlerInterface $taxHandler
     * @return mixed
     */
    public function setTaxHandler(TaxHandlerInterface $taxHandler);
} 