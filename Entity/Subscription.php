<?php

namespace Sparkling\AdyenBundle\Entity;

abstract class Subscription
{
    /**
     * @return int
     */
    abstract public function getId();

    /**
     * @param null|bool $set
     * @return bool
     */
    abstract public function isExpired($set = null);

    /**
     * @param null|bool $set
     * @return bool
     */
    abstract public function isTrial($set = null);

    /**
     * @return int
     */
    abstract public function getTrialDaysLeft();

    /**
     * @param null|bool $set
     * @return bool
     */
    abstract public function hasChargePending($set = null);

    /**
     * @param null|bool $set
     * @return bool
     */
    abstract public function hasRecurringSetup($set = null);

    /**
     * @return string
     */
    abstract public function getEmail();

    /**
     * @return \DateTime
     */
    abstract public function getPlanExpiresAt();

    /**
     * @param \DateTime $plan_expires_at
     */
    abstract public function setPlanExpiresAt(\DateTime $plan_expires_at);

    /**
     * @return string
     */
    abstract public function getRecurringReference();

    /**
     * @param string $recurring_reference
     */
    abstract public function setRecurringReference($recurring_reference);

    /**
     * @return string
     */
    abstract public function getCardHolder();

    /**
     * @param string $card_holder
     */
    abstract public function setCardHolder($card_holder);

    /**
     * @return string
     */
    abstract public function getCardNumber();

    /**
     * @param string $card_number
     */
    abstract public function setCardNumber($card_number);

    /**
     * @return string
     */
    abstract public function getCardExpiryMonth();

    /**
     * @param string $card_expiry_month
     */
    abstract public function setCardExpiryMonth($card_expiry_month);

    /**
     * @return string
     */
    abstract public function getCardExpiryYear();

    /**
     * @param string $card_expiry_year
     * @return mixed
     */
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

        $clone = clone $this->getPlanExpiresAt();
        $this->setPlanExpiresAt($clone->modify('+1 month'));
    }
}
