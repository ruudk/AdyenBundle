<?php

namespace Sparkling\AdyenBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Sparkling\AdyenBundle\Entity\Transaction;

class ChargeEvent extends Event
{
    /**
     * @var \Sparkling\AdyenBundle\Entity\Transaction
     */
    protected $transaction;

    /**
     * @var bool
     */
    protected $success = false;

    /**
     * @param \Sparkling\AdyenBundle\Entity\Transaction $transaction
     * @param bool                                      $success
     */
    public function __construct(Transaction $transaction, $success)
	{
		$this->transaction = $transaction;
		$this->success = (bool) $success;
	}

    /**
     * @return \Sparkling\AdyenBundle\Entity\Transaction
     */
    public function getTransaction()
	{
		return $this->transaction;
	}

    /**
     * @return bool
     */
    public function isSucces()
	{
		return $this->success;
	}
}
