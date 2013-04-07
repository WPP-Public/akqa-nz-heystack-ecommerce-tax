<?php

use Camspiers\DependencyInjection\SharedContainerFactory;
use Heystack\Subsystem\Tax\DependencyInjection\ContainerExtension;

SharedContainerFactory::addExtension(new ContainerExtension());
