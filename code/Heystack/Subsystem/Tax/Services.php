<?php
/**
 * This file is part of the Ecommerce-Tax package
 *
 * @package Ecommerce-Tax
 */

/**
 * Payment namespace
 */
namespace Heystack\Subsystem\Tax;

/**
 * Holds constants corresponding to the services defined in the services.yml file
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Tax
 */
final class Services
{
    /**
     * Holds the identfier of the tax handler
     * For use with the ServiceStore::getService($identifier) call
     */
    const TAX_HANDLER = 'tax_handler';
}
