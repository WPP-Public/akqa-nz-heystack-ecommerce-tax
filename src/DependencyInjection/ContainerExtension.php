<?php
/**
 * This file is part of the Ecommerce-Tax package
 *
 * @package Ecommerce-Tax
 */

/**
 * Tax namespace
 */
namespace Heystack\Tax\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

use Heystack\Core\Exception\ConfigurationException;
use Heystack\Tax\Services;

/**
 * Container extension for Heystack.
 *
 * If Heystacks services are loaded as an extension (this happens when there is
 * a primary services.yml file in mysite/config) then this is the container
 * extension that loads heystacks services.yml
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @author Cam Spiers <cameron@heyday.co.nz>
 * @package Ecommerce-Tax
 *
 */
class ContainerExtension implements ExtensionInterface
{

    /**
     * Loads a services.yml file into a fresh container, ready to me merged
     * back into the main container
     *
     * @param  array $config
     * @param  \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @throws \Heystack\Core\Exception\ConfigurationException
     * @return void
     */
    public function load(array $config, ContainerBuilder $container)
    {
        (new YamlFileLoader(
            $container,
            new FileLocator(ECOMMERCE_TAX_BASE_PATH . '/config')
        ))->load('services.yml');

        // TODO: Eventually replace this with config processor
        $config = array_pop($config);

        if (isset($config['config']) && $container->hasDefinition(Services::TAX_HANDLER)) {
            $container->getDefinition(Services::TAX_HANDLER)->addMethodCall('setConfig', [$config['config']]);
        } else {
            throw new ConfigurationException('Please configure the tax subsystem on your /mysite/config/services.yml file');
        }
    }

    /**
     * Returns the namespace of the container extension
     * @return string
     */
    public function getNamespace()
    {
        return 'tax';
    }

    /**
     * Returns Xsd Validation Base Path, which is not used, so false
     * @return boolean
     */
    public function getXsdValidationBasePath()
    {
        return false;
    }

    /**
     * Returns the container extensions alias
     * @return string
     */
    public function getAlias()
    {
        return 'tax';
    }
}
