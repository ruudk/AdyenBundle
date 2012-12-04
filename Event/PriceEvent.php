<?php

namespace Sparkling\AdyenBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Sparkling\AdyenBundle\Entity\Subscription;
use Sparkling\AdyenBundle\Entity\Plan;

class PriceEvent extends Event
{
    /**
     * @var \Sparkling\AdyenBundle\Entity\Subscription
     */
    protected $subscription;

    /**
     * @var \Sparkling\AdyenBundle\Entity\Plan
     */
    protected $plan;

    /**
     * The currency of the charge. You cannot only change this via the CurrencyEvent
     *
     * @var string
     */
    protected $currency;

    /**
     * @var float
     */
    protected $price;

    /**
     * @var float
     */
    protected $tax;

    /**
     * @var float
     */
    protected $discount;

    /**
     * @param \Sparkling\AdyenBundle\Entity\Subscription $subscription
     * @param \Sparkling\AdyenBundle\Entity\Plan    $plan
     * @param string                                $currency
     */
    public function __construct(Subscription $subscription, Plan $plan, $currency)
	{
		$this->subscription = $subscription;
		$this->plan = $plan;
        $this->currency = $currency;
		$this->price = $plan->getPrice();
		$this->tax = $plan->getTax();
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
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @return float
     */
    public function getPrice()
	{
		return $this->price;
	}

    /**
     * @param float $price
     */
    public function setPrice($price)
	{
		$this->price = $price;
	}

    /**
     * @return float
     */
    public function getTax()
	{
		return $this->tax;
	}

    /**
     * @param float $tax
     */
    public function setTax($tax)
	{
		$this->tax = $tax;
	}

	/**
	 * @return float
	 */
	public function getDiscount()
	{
		return $this->discount;
	}

	/**
	 * @param float
	 */
    public function setDiscount($discount)
	{
		$this->discount = $discount;
	}

    /**
     * @param bool $applyDiscount
     * @return float
     */
    public function getCents($applyDiscount = true)
	{
		$price = $this->price;

		if($applyDiscount && isset($this->discount))
			$price -= $price * ($this->discount / 100);

		if(isset($this->tax) && $this->tax > 0)
			$price *= 1 + ($this->tax / 100);

		return round($price * 100, 0);
	}
}
