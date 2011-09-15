<?php

namespace Sparkling\AdyenBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adyen bundle
 *
 * @author Ruud Kamphuis <ruud@1plus1media.nl>
 */
class SparklingAdyenBundle extends Bundle
{
	public function build(ContainerBuilder $container)
    {
        parent::build($container);
    }
}
