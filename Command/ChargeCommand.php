<?php

namespace Sparkling\AdyenBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Command\Command;

class ChargeCommand extends Command
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
		$this->setName('adyen:charge');
		$this->setDescription('Charge accounts that need to be renewed');
	}


	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$expired = $this->em->getRepository($this->container->getParameter('adyen.account_entity'))->getAccountsThatNeedToBeCharged();
		if($expired)
		{
			$output->writeln("Charging " . (count($expired) == 1 ? "1 account" : count($expired) . " accounts"));
			$output->writeLn('');

			foreach($expired AS $account)
			{
				if($account->getRecurringReference() === null)
				{
					if($this->container->get('adyen.service')->loadContract($account) !== true)
					{
						$output->writeln(sprintf('%s <error>%s</error>', $account->getName(), 'Recurring contract not found'));
						continue;
					}
				}

				$charge = $this->container->get('adyen.service')->charge($account);

				if($charge === false)
					$output->writeln(sprintf('%s <error>%s</error>', $account->getName(), $this->container->get('adyen.service')->getError()));
				else
					$output->writeln(sprintf('%s <comment>%s</comment>', $account->getName(), 'Done'));

				$this->em->flush();
			}
		}
		else $output->writeln('There are accounts that need to be charged.');
	}
}