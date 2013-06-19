<?php

namespace Sparkling\AdyenBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Command\Command;

class ChargeCommand extends Command
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var \Sparkling\AdyenBundle\Service\AdyenService
     */
    protected $adyen;

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->container = $this->getApplication()->getKernel()->getContainer();

        $this->em = $this->container->get('adyen.orm_entity_manager');
        $this->adyen = $this->container->get('adyen.service');
    }

    protected function configure()
    {
        $this->setName('adyen:charge');
        $this->setDescription('Charge subscriptions that need to be renewed');
        $this->setDefinition(array(
            new InputArgument(
                'subscription',
                InputArgument::OPTIONAL,
                'The ID of the subscription you want to charge (optional)'
            )
        ));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * @var \Sparkling\AdyenBundle\Entity\SubscriptionRepositoryInterface $repository
         */
        $repository = $this->em->getRepository($this->container->getParameter('adyen.subscription_entity'));

        if ($input->getArgument('subscription') !== null) {
            if ($subscription = $repository->find($input->getArgument('subscription'))) {
                $expired = array($subscription);
            } else return $output->writeln('<error>Subscription not found</error>');
        } else {
            $expired = $repository->getSubscriptionsThatNeedToBeCharged();
        }

        if ($expired) {
            $output->writeln("Charging " . (count($expired) == 1 ? "1 subscription" : count($expired) . " subscriptions"));
            $output->writeLn('');

            foreach ($expired AS $subscription) {
                if ($subscription->getRecurringReference() === null) {
                    if ($this->adyen->loadContract($subscription) !== true) {
                        $output->writeln(sprintf('Subscription %d <error>%s</error>', $subscription->getId(), 'Recurring contract not found'));
                        continue;
                    }
                }

                $transaction = $this->adyen->charge($subscription);

                if($transaction === FALSE)
                    $output->writeln(sprintf('Subscription %d <error>%s</error>', $subscription->getId(), $this->adyen->getError()));
                else {
                    $output->writeln(sprintf(
                        'Subscription %d -> Transaction %d [<info>OK</info>]',
                        $subscription->getId(),
                        $transaction->getId()
                    ));
                }
            }

            $this->em->flush();
        } else $output->writeln('There are subscriptions that need to be charged.');
    }
}
