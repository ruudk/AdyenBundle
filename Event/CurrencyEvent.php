<?php

namespace Sparkling\AdyenBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Sparkling\AdyenBundle\Entity\Subscription;
use Sparkling\AdyenBundle\Entity\Plan;

class CurrencyEvent extends Event
{
    const EURO = 'EUR';
    const DOLLAR = 'USD';

    /**
     * @var \Sparkling\AdyenBundle\Entity\Subscription
     */
    protected $subscription;

    /**
     * @var \Sparkling\AdyenBundle\Entity\Plan
     */
    protected $plan;

    /**
     * @var string
     */
    protected $currency;

    /**
     * @param \Sparkling\AdyenBundle\Entity\Subscription $subscription
     * @param \Sparkling\AdyenBundle\Entity\Plan         $plan
     * @param string                                     $defaultCurrency
     */
    public function __construct(Subscription $subscription, Plan $plan, $defaultCurrency)
    {
        $this->subscription = $subscription;
        $this->plan = $plan;
        $this->currency = $defaultCurrency;
    }

    /**
     * @return \Sparkling\AdyenBundle\Entity\Subscription
     */
    public function getSubscription()
    {
        return $this->subscription;
    }

    /**
     * @return \Sparkling\AdyenBundle\Entity\Plan
     */
    public function getPlan()
    {
        return $this->plan;
    }

    /**
     * @param string $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }
}
