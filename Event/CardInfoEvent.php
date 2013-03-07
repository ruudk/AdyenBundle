<?php

namespace Sparkling\AdyenBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Sparkling\AdyenBundle\Entity\Subscription;

class CardInfoEvent extends Event
{
    /**
     * @var \Sparkling\AdyenBundle\Entity\Subscription
     */
    protected $subscription;

    /**
     * @var array
     */
    protected $contracts;

    /**
     * @param \Sparkling\AdyenBundle\Entity\Subscription $subscription
     * @param array                                      $contracts
     */
    public function __construct(Subscription $subscription, array $contracts)
    {
        $this->subscription = $subscription;
        $this->contracts = $contracts;
    }

    /**
     * @return \Sparkling\AdyenBundle\Entity\Subscription
     */
    public function getSubscription()
    {
        return $this->subscription;
    }

    /**
     * @return array
     */
    public function getContracts()
    {
        return $this->contracts;
    }
}
