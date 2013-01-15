<?php

namespace Sparkling\AdyenBundle\Entity;

abstract class Plan
{
    abstract public function getId();

    abstract public function getName();
    abstract public function setName($name);

    abstract public function getPrice();
    abstract public function setPrice($price);

    abstract public function getTax();
    abstract public function setTax($tax);

    abstract public function getTrial();
    abstract public function setTrial($trial);
}
