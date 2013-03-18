<?php

namespace Sparkling\AdyenBundle\Entity;

abstract class Plan
{
    /**
     * @return int
     */
    abstract public function getId();

    /**
     * @return string
     */
    abstract public function getName();

    /**
     * @param string $name
     */
    abstract public function setName($name);

    /**
     * @return float
     */
    abstract public function getPrice();

    /**
     * @param float $price
     */
    abstract public function setPrice($price);

    /**
     * @return float
     */
    abstract public function getTax();

    /**
     * @param float $tax
     */
    abstract public function setTax($tax);

    /**
     * @return int
     */
    abstract public function getTrial();

    /**
     * @param int $trial
     */
    abstract public function setTrial($trial);
}
