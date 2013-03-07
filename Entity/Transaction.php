<?php

namespace Sparkling\AdyenBundle\Entity;

abstract class Transaction
{
    abstract public function getId();

    /**
     * @return \Sparkling\AdyenBundle\Entity\Subscription
     */
    abstract public function getSubscription();

    /**
     * @param \Sparkling\AdyenBundle\Entity\Subscription $subscription
     */
    abstract public function setSubscription(Subscription $subscription);

    /**
     * @return \Sparkling\AdyenBundle\Entity\Plan
     */
    abstract public function getPlan();

    /**
     * @param \Sparkling\AdyenBundle\Entity\Plan $plan
     */
    abstract public function setPlan(Plan $plan);

    abstract public function getReference();
    abstract public function setReference($reference);

    abstract public function getType();
    abstract public function setType($type);

    abstract public function getCurrency();
    abstract public function setCurrency($currency);

    abstract public function getAmount();
    abstract public function setAmount($amount);

    abstract public function getTax();
    abstract public function setTax($tax);

    abstract public function getDiscount();
    abstract public function setDiscount($discount);

    abstract public function isProcessed($set = null);
    abstract public function isCancelled($set = null);
    abstract public function isSuccess($set = null);

    abstract public function getLog();
    abstract public function log($message);
}
