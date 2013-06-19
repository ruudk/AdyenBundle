<?php

namespace Sparkling\AdyenBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * Configuration
 *
 * @author Ruud Kamphuis <ruud@1plus1media.nl>
 */
class Configuration
{
    public function getConfigTree()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('adyen', 'array');

        $rootNode
            ->children()
                ->scalarNode('platform')
                    ->validate()
                        ->ifNotInArray(array('live', 'test'))
                        ->thenInvalid('The %s platform is not supported')
                    ->end()
                ->end()
                ->scalarNode('merchant_account')->isRequired()->end()
                ->scalarNode('skin')->isRequired()->end()
                ->scalarNode('shared_secret')->isRequired()->end()
                ->scalarNode('currency')->defaultValue('USD')->end()
                ->scalarNode('update_charge_amount')->defaultValue('2')->end()
                ->scalarNode('subscription_entity')->isRequired()->end()
                ->scalarNode('plan_entity')->isRequired()->end()
                ->scalarNode('transaction_entity')->isRequired()->end()
                ->scalarNode('webservice_username')->isRequired()->end()
                ->scalarNode('webservice_password')->isRequired()->end()
                ->scalarNode('payment_methods')->defaultValue('mc,visa,amex')->end()
                ->scalarNode('orm_entity_manager')->defaultValue('doctrine.orm.default_entity_manager')->end()
            ->end();

        return $treeBuilder->buildTree();
    }
}
