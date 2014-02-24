<?php
/**
 * This file is part of the Ecommerce-Tax package
 *
 * @package Ecommerce-Tax
 */

/**
 * Shipping namespace
 */
namespace Heystack\Subsystem\Tax;

/**
 * Events holds constant references to triggerable dispatch events.
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Tax
 * @see Symfony\Component\EventDispatcher
 *
 */
final class Events
{
    /**
     * Indicates that the TaxHandler's total has been updated
     */
    const TOTAL_UPDATED       = 'tax.totalupdated';

    /**
     * Indicates that the TaxHandler's information has been stored
     */
    const STORED              = 'tax.stored';
}
