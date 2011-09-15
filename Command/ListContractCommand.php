<?php

namespace Sparkling\AdyenBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
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

		$this->em = $this->container->get('doctrine.orm.default_entity_manager');
	}

	protected function configure()
	{
		$this->setName('adyen:contract:list');
		$this->setDescription('List contracts per account');
		$this->setDefinition(array(new InputArgument(
            'account',
			InputArgument::REQUIRED,
			'The ID of the account'
        )));
	}


	protected function execute(InputInterface $input, OutputInterface $output)
	{
		if($account = $this->em->getRepository($this->container->getParameter('adyen.account_entity'))->find($input->getArgument('account')))
		{
			if($contracts = $this->container->get('adyen.service')->getContracts($account))
			{
				$first = true;
				foreach($contracts AS $contract)
				{
					if(!$first) $output->writeln('---');

					$output->writeln('creationDate: ' . $contract['creationDate']);
					$output->writeln('recurringDetailReference: ' . $contract['recurringDetailReference']);

					$output->writeln('card.holderName: ' . $contract['card']['holderName']);
					$output->writeln('card.number: ' . $contract['card']['number']);
					$output->writeln('card.expiryMonth: ' . $contract['card']['expiryMonth']);
					$output->writeln('card.variant: ' . $contract['variant']);

					$first = false;
				}
			}
			else $output->writeln('No contracts found');
		}
		else $output->writeln('Account not found');
	}
}