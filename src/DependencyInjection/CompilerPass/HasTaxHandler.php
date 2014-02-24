<?php


namespace Heystack\Tax\DependencyInjection\CompilerPass;

use Heystack\Tax\Services;
use Heystack\Core\DependencyInjection\CompilerPass\HasService;

/**
 * Class HasTaxHandler
 * @package Heystack\Tax\DependencyInjection\CompilerPass
 */
class HasTaxHandler extends HasService
{
    /**
     * The name of the service in the container
     * @return string
     */
    protected function getServiceName()
    {
        return Services::TAX_HANDLER;
    }

    /**
     * The method name used to set the service
     * @return string
     */
    protected function getServiceSetterName()
    {
        return 'setTaxHandler';
    }
} 