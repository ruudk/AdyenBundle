<?php

namespace Sparkling\AdyenBundle\Entity;

abstract class Transaction
{
    /**
     * @return int
     */
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

    /**
     * @return string
     */
    abstract public function getReference();

    /**
     * @param string $reference
     */
    abstract public function setReference($reference);

    /**
     * @return string
     */
    abstract public function getType();

    /**
     * @param string $type
     */
    abstract public function setType($type);

    /**
     * @return string
     */
    abstract public function getCurrency();

    /**
     * @param string $currency
     */
    abstract public function setCurrency($currency);

    /**
     * @return float
     */
    abstract public function getAmount();

    /**
     * @param float $amount
     */
    abstract public function setAmount($amount);

    /**
     * @return float
     */
    abstract public function getTax();

    /**
     * @param float $tax
     */
    abstract public function setTax($tax);

    /**
     * @return float
     */
    abstract public function getDiscount();

    /**
     * @param float $discount
     */
    abstract public function setDiscount($discount);

    /**
     * @param null|bool $set
     * @return bool
     */
    abstract public function isProcessed($set = null);

    /**
     * @param null|bool $set
     * @return bool
     */
    abstract public function isCancelled($set = null);

    /**
     * @param null|bool $set
     * @return bool
     */
    abstract public function isSuccess($set = null);

    /**
     * @return string
     */
    abstract public function getLog();

    /**
     * @param string $message
     */
    abstract public function log($message);
}
