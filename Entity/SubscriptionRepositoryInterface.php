<?php

namespace Sparkling\AdyenBundle\Entity;

interface SubscriptionRepositoryInterface
{
    /**
     * @return array|\Sparkling\AdyenBundle\Entity\Subscription[]
     */
    public function getSubscriptionsThatNeedToBeCharged();

    /**
     * @param int $days
     * @return array|\Sparkling\AdyenBundle\Entity\Subscription[]
     */
    public function getTrialSubscriptionsThatExpireIn($days);
}