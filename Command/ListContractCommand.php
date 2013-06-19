<?php

namespace Sparkling\AdyenBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Command\Command;

class ListContractCommand extends Command
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;
    protected $em;

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->container = $this->getApplication()->getKernel()->getContainer();

        $this->em = $this->container->get($this->container->getParameter('adyen.orm_entity_manager'));
    }

    protected function configure()
    {
        $this->setName('adyen:contract:list');
        $this->setDescription('List contracts per subscription');
        $this->setDefinition(array(new InputArgument(
            'subscription',
            InputArgument::REQUIRED,
            'The ID of the subscription'
        )));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($subscription = $this->em->getRepository($this->container->getParameter('adyen.subscription_entity'))->find($input->getArgument('subscription'))) {
            if ($contracts = $this->container->get('adyen.service')->getContracts($subscription)) {
                $first = true;
                foreach ($contracts AS $contract) {
                    if(!$first) $output->writeln('---');

                    $output->writeln('creationDate: ' . $contract['creationDate']);
                    $output->writeln('recurringDetailReference: ' . $contract['recurringDetailReference']);

                    $output->writeln('card.holderName: ' . $contract['card']['holderName']);
                    $output->writeln('card.number: ' . $contract['card']['number']);
                    $output->writeln('card.expiryMonth: ' . $contract['card']['expiryMonth']);
                    $output->writeln('card.variant: ' . $contract['variant']);

                    $first = false;
                }
            } else $output->writeln('No contracts found');
        } else $output->writeln('Subscription not found');
    }
}
