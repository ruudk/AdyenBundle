<?php

namespace Sparkling\AdyenBundle\Tests\DependencyInjection;

use Sparkling\AdyenBundle\DependencyInjection\SparklingAdyenExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Code bases on FOSUserBundle tests
 */
class ExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerBuilder
     */
    protected $containerBuilder;

    protected function setUp()
    {
        $this->containerBuilder = new ContainerBuilder();
        $this->containerBuilder->setParameter('kernel.logs_dir', 'logs');

        $this->containerBuilder->setDefinition('event_dispatcher', new Definition('ContainerAwareTraceableEventDispatcher'));
        $this->containerBuilder->setDefinition('doctrine.orm.default_entity_manager', new Definition('EntityManager'));
    }

    public function testBasicConfiguration()
    {
        $loader = new SparklingAdyenExtension();

        $config = $this->getDefaultConfig();
        $loader->load(array($config), $this->containerBuilder);

        $this->containerBuilder->compile();

        $definition = $this->containerBuilder->getAlias('adyen.orm_entity_manager');
        $this->assertEquals('doctrine.orm.default_entity_manager', $definition->__toString());
    }

    public function testCustomEntityManager()
    {
        /**
         * Create entity manager service
         */
        $this->containerBuilder->setDefinition('my_custom_entity_manager', new Definition('App\MyEntityManager'));

        $loader = new SparklingAdyenExtension();

        $config = $this->getDefaultConfig();
        $config['orm_entity_manager'] = 'my_custom_entity_manager';

        $loader->load(array($config), $this->containerBuilder);

        $this->containerBuilder->compile();

        $definition = $this->containerBuilder->getAlias('adyen.orm_entity_manager');
        $this->assertEquals('my_custom_entity_manager', $definition->__toString());
    }

    /**
     * @return array
     */
    private function getDefaultConfig()
    {
        return array(
            'platform'             => 'test',
            'merchant_account'     => 'abc123',
            'skin'                 => '12345',
            'shared_secret'        => 'iojgewr8gmw3fhdbdg',
            'subscription_entity'  => 'AppBundle\Entity\Subscription',
            'plan_entity'          => 'AppBundle\Entity\Plan',
            'transaction_entity'   => 'AppBundle\Entity\Transaction',
            'webservice_username'  => 'user',
            'webservice_password'  => 'password',
        );
    }
}