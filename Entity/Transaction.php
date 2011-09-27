<?php

namespace Sparkling\AdyenBundle\Entity;

abstract class Transaction
{
	abstract public function getId();

	abstract public function getAccount();
	abstract public function setAccount(Account $account);

	abstract public function getReference();
	abstract public function setReference($reference);

	abstract public function getType();
	abstract public function setType($type);

	abstract public function getAmount();
	abstract public function setAmount($amount);

	abstract public function getCurrency();
	abstract public function setCurrency($currency);

	abstract public function isProcessed($set = null);
	abstract public function isCancelled($set = null);

	abstract public function getLog();
	abstract public function log($message);
}