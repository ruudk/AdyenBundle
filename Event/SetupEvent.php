<?php

namespace Sparkling\AdyenBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Sparkling\AdyenBundle\Entity\Transaction;

class SetupEvent extends Event
{
    /**
     * @var \Sparkling\AdyenBundle\Entity\Transaction
     */
    protected $transaction;

    /**
     * @param \Sparkling\AdyenBundle\Entity\Subscription $transaction
     */
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * @return \Sparkling\AdyenBundle\Entity\Transaction
     */
    public function getTransaction()
    {
        return $this->transaction;
    }
}