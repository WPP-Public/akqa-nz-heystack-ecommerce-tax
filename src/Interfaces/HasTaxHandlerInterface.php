<?php

namespace Heystack\Tax\Interfaces;

/**
 * Interface HasTaxHandlerInterface
 * @package Heystack\Tax\Interfaces
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