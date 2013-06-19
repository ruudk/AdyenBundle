<?php

namespace Sparkling\AdyenBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Command\Command;

class DisableContractCommand extends Command
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;
    protected $em;

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->container = $this->getApplication()->getKernel()->getContainer();

        $this->em = $this->container->get('adyen.orm_entity_manager');
    }

    protected function configure()
    {
        $this->setName('adyen:contract:disable');
        $this->setDescription('Disable contract(s) for subscription');
        $this->setDefinition(array(
            new InputArgument(
                'subscription',
                InputArgument::REQUIRED,
                'The ID of the subscription'
            ),
            new InputArgument(
                'reference',
                InputArgument::OPTIONAL,
                'The reference of the contract'
            )
        ));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($subscription = $this->em->getRepository($this->container->getParameter('adyen.subscription_entity'))->find($input->getArgument('subscription'))) {
            if ($this->container->get('adyen.service')->disable($subscription, $input->getArgument('reference'))) {
                if($input->getArgument('reference') == null)
                    $output->writeln(sprintf('Disable all contracts for subscription %s <info>[ OK ]</info>', $input->getArgument('subscription')));
                else
                    $output->writeln(sprintf('Disable contract %s <info>[ OK ]</info>', $input->getArgument('reference')));
            } else {
                if($input->getArgument('reference') == null)
                    $output->writeln(sprintf('Disable all contracts for subscription %s <error>[ Failed ]</error>', $input->getArgument('subscription')));
                else
                    $output->writeln(sprintf('Disable contract %s <error>[ Failed ]</error>', $input->getArgument('reference')));
            }
        } else $output->writeln('<error>Subscription not found</error>');

        $this->em->flush();
    }
}
