<?php

namespace Sparkling\AdyenBundle\Service;

use Sparkling\AdyenBundle\Entity\Account;
use Sparkling\AdyenBundle\Entity\Plan;

class NotificationService
{
	/**
     * @var \Sparkling\AdyenBundle\Service\AdyenService
     */
	protected $adyen;

	/**
     * @var \Doctrine\ORM\EntityManager
     */
	protected $em;

	public function __construct(AdyenService $adyen)
	{
		$this->adyen = $adyen;
	}

	public function setEntityManager($em)
	{
		$this->em = $em;
	}

	public function sendNotification($request)
	{
		if(is_array($request->notification->notificationItems->NotificationRequestItem))
		{
			foreach($request->notification->notificationItems->NotificationRequestItem AS $item)
				$this->process($item);
		}
		else
		{
			$this->process($request->notification->notificationItems->NotificationRequestItem);
		}

		$this->em->flush();

		return array("notificationResponse" => "[accepted]");
	}

	protected function process($item)
	{
		$output = print_r($item, true) . PHP_EOL;

		$this->adyen->processNotification(array(
			'merchantReference' => $item->merchantReference,
			'pspReference'      => $item->pspReference,
			'paymentMethod'     => $item->paymentMethod,
			'reason'            => $item->reason,
			'authResult'        => $item->eventCode ?: $item->authResult
		));

		file_put_contents(__DIR__ . '../../../../../adyen.log', $output, FILE_APPEND);
	}
}