<?php

namespace Sparkling\AdyenBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Sparkling\AdyenBundle\Entity\Transaction;

class ChargeEvent extends Event
{
    protected $transaction;
	protected $success = false;
    protected $result;

    public function __construct(Transaction $transaction, $success, \stdClass $result)
    {
        $this->transaction = $transaction;
	    $this->success = (bool) $success;
        $this->result = $result;
    }

    public function getTransaction()
    {
        return $this->transaction;
    }

	public function isSucces()
	{
		return $this->success;
	}

    public function getResult()
    {
        return $this->result;
    }
}
