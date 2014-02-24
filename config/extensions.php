<?php

use Camspiers\DependencyInjection\SharedContainerFactory;
use Heystack\Tax\DependencyInjection\ContainerExtension;
use Heystack\Tax\DependencyInjection\CompilerPass\HasTaxHandler;

SharedContainerFactory::addExtension(new ContainerExtension());
SharedContainerFactory::addCompilerPass(new HasTaxHandler());
