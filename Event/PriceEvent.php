<?php

namespace Sparkling\AdyenBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Sparkling\AdyenBundle\Entity\Account;
use Sparkling\AdyenBundle\Entity\Plan;

class PriceEvent extends Event
{
	protected $account;
	protected $plan;
	protected $price;
	protected $tax;
	protected $discount;

	public function __construct(Account $account, Plan $plan)
	{
		$this->account = $account;
		$this->plan = $plan;

		$this->price = $plan->getPrice();
		$this->tax = $plan->getTax();
	}

	public function getAccount()
	{
		return $this->account;
	}

	public function getPlan()
	{
		return $this->plan;
	}

	public function getPrice()
	{
		return $this->price;
	}

	public function setPrice($price)
	{
		$this->price = $price;
	}

	public function getTax()
	{
		return $this->tax;
	}

	public function setTax($tax)
	{
		$this->tax = $tax;
	}

	/**
	 * @return decimal Discount percentage
	 */
	public function getDiscount()
	{
		return $this->discount;
	}

	/**
	 * @param decimal $discount Discount percentage
	 * @return void
	 */
	public function setDiscount($discount)
	{
		$this->discount = $discount;
	}

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
