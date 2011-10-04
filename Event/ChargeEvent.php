<?php

namespace Sparkling\AdyenBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Sparkling\AdyenBundle\Entity\Transaction;

class ChargeEvent extends Event
{
	protected $transaction;
	protected $success = false;

	public function __construct(Transaction $transaction, $success)
	{
		$this->transaction = $transaction;
		$this->success = (bool) $success;
	}

	public function getTransaction()
	{
		return $this->transaction;
	}

	public function isSucces()
	{
		return $this->success;
	}
}
