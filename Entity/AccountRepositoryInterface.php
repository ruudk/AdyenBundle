<?php

namespace Sparkling\AdyenBundle\Entity;

interface AccountRepositoryInterface
{
	public function getAccountsThatNeedToBeCharged();
	public function getTrialAccountsThatExpireIn($days);
}