<?php

namespace Sparkling\AdyenBundle\Entity;

abstract class Subscription
{
    abstract public function getId();

	abstract public function isExpired($set = null);

	abstract public function isTrial($set = null);
	abstract public function getTrialDaysLeft();

	abstract public function hasChargePending($set = null);

	abstract public function hasRecurringSetup($set = null);

    abstract public function getEmail();

	abstract public function getPlanExpiresAt();
	abstract public function setPlanExpiresAt(\DateTime $plan_expires_at);

	abstract public function getRecurringReference();
	abstract public function setRecurringReference($recurring_reference);

	abstract public function getCardHolder();
	abstract public function setCardHolder($card_holder);

	abstract public function getCardNumber();
	abstract public function setCardNumber($card_number);

	abstract public function getCardExpiryMonth();
	abstract public function setCardExpiryMonth($card_expiry_month);

	abstract public function getCardExpiryYear();
	abstract public function setCardExpiryYear($card_expiry_year);

    /**
     * @return \Sparkling\AdyenBundle\Entity\Plan
     */
    abstract public function getPlan();

    /**
     * @param Plan $plan
     */
    abstract public function setPlan(Plan $plan);

	public function extendPlan()
	{
		$this->hasChargePending(false);
		$this->isExpired(false);
		$this->setPlanExpiresAt(new \DateTime('+1 month'));
	}
}