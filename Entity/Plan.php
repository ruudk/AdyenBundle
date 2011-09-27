<?php

namespace Sparkling\AdyenBundle\Entity;

abstract class Plan
{
	abstract public function getId();

	abstract public function getName();
	abstract public function setName($name);

	abstract public function getCurrency();
	abstract public function setCurrency($currency);

	abstract public function getCurrencySign();
	abstract public function setCurrencySign($currency_sign);

	abstract public function getPrice();
	abstract public function setPrice($price);

	abstract public function getPriceFormatted();

	abstract public function getTax();
	abstract public function setTax($tax);

	abstract public function getTrial();
	abstract public function setTrial($trial);

	public function isFree()
	{
		return $this->getPrice() == 0;
	}

	public function isPaid()
	{
		return $this->getPrice() > 0;
	}
}