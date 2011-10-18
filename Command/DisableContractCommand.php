<?php

namespace Sparkling\AdyenBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
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

		$this->em = $this->container->get('doctrine.orm.default_entity_manager');
	}

	protected function configure()
	{
		$this->setName('adyen:contract:disable');
		$this->setDescription('Disable contract(s) for account');
		$this->setDefinition(array(
		    new InputArgument(
                'account',
				InputArgument::REQUIRED,
				'The ID of the account'
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
		if($account = $this->em->getRepository($this->container->getParameter('adyen.account_entity'))->find($input->getArgument('account')))
		{
			if($this->container->get('adyen.service')->disable($account, $input->getArgument('reference')))
			{
				if($input->getArgument('reference') == null)
					$output->writeln(sprintf('Disable all contracts for account %s <info>[ OK ]</info>', $input->getArgument('account')));
				else
					$output->writeln(sprintf('Disable contract %s <info>[ OK ]</info>', $input->getArgument('reference')));
			}
			else
			{
				if($input->getArgument('reference') == null)
					$output->writeln(sprintf('Disable all contracts for account %s <error>[ Failed ]</error>', $input->getArgument('account')));
				else
					$output->writeln(sprintf('Disable contract %s <error>[ Failed ]</error>', $input->getArgument('reference')));
			}

			$this->em->flush();
		}
		else $output->writeln('<error>Account not found</error>');
	}
}