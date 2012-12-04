<?php

namespace Sparkling\AdyenBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Config\Definition\Processor;

/**
 * Adyen Extension
 *
 * @author Ruud Kamphuis <ruud@1plus1media.nl>
 */
class SparklingAdyenExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $processor     = new Processor();
        $configuration = new Configuration();

        $config = $processor->process($configuration->getConfigTree($container->getParameter('kernel.debug')), $configs);

        $loader->load('config.xml');

		$container->setParameter('adyen.platform', $config['platform']);
	    $container->setParameter('adyen.skin', $config['skin']);
	    $container->setParameter('adyen.merchant_account', $config['merchant_account']);
	    $container->setParameter('adyen.shared_secret', $config['shared_secret']);
	    $container->setParameter('adyen.currency', $config['currency']);
	    $container->setParameter('adyen.subscription_entity', $config['subscription_entity']);
	    $container->setParameter('adyen.plan_entity', $config['plan_entity']);
	    $container->setParameter('adyen.transaction_entity', $config['transaction_entity']);
	    $container->setParameter('adyen.webservice_username', $config['webservice_username']);
	    $container->setParameter('adyen.webservice_password', $config['webservice_password']);
    }

	public function getXsdValidationBasePath()
	{
		return __DIR__.'/../Resources/config/';
	}

	public function getNamespace()
	{
		return 'http://schema.sparklingapp.com/adyen';
	}
}
