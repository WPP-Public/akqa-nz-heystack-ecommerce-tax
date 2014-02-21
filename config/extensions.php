<?php

use Camspiers\DependencyInjection\SharedContainerFactory;
use Heystack\Subsystem\Tax\DependencyInjection\ContainerExtension;
use Heystack\Subsystem\Tax\DependencyInjection\CompilerPass\HasTaxHandler;

SharedContainerFactory::addExtension(new ContainerExtension());
SharedContainerFactory::addCompilerPass(new HasTaxHandler());
