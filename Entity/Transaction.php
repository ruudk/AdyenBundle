<?php

namespace Sparkling\AdyenBundle\Entity;

abstract class Transaction
{
	abstract public function getAccount();
	abstract public function setAccount(Account $account);
}