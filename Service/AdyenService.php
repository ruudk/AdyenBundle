<?php

namespace Sparkling\AdyenBundle\Service;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Sparkling\AdyenBundle\Entity\Account;
use Sparkling\AdyenBundle\Entity\Plan;
use Sparkling\AdyenBundle\Entity\Transaction;
use Sparkling\AdyenBundle\Event\ChargeEvent;

class AdyenService
{
	public $platform;
	public $merchantAccount;
	public $skin;
	public $sharedSecret;
	public $currency;
	public $entities = array();
	public $webservice = array();
	protected $error;

	/**
	 * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
	 */
	protected $dispatcher;

	/**
     * @var \Doctrine\ORM\EntityManager
     */
	protected $em;

	public function __construct($platform, $merchantAccount, $skin, $sharedSecret, $currency, array $entities, array $webservice)
	{
		$this->platform = $platform;
		$this->merchantAccount = $merchantAccount;
		$this->skin = $skin;
		$this->sharedSecret = $sharedSecret;
		$this->currency = $currency;
		$this->entities = $entities;
		$this->webservice = $webservice;
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
	 * @return void
	 */
	public function setEventDispatcher(EventDispatcherInterface $dispatcher)
	{
		$this->dispatcher = $dispatcher;
	}

	public function setEntityManager($em)
	{
		$this->em = $em;
	}

	/**
	 * @param Account $account
	 * @param Plan $plan
	 * @param  $returnUrl
	 * @return Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function setup(Account $account, Plan $plan, $returnUrl)
	{
		$paymentAmount = $this->priceToCents($account->getPlanPrice());

		$transaction = new $this->entities['transaction'];
		$transaction->setAccount($account);
		$transaction->setType('setup');
		$transaction->setAmount($paymentAmount);
		$transaction->setCurrency($account->getPlanCurrency());

		$this->em->persist($transaction);
		$this->em->flush();

		$today = new \DateTime();
		$parameters = array(
			'merchantReference' => 'Setup ' . $transaction->getId(),
			'paymentAmount'     => $paymentAmount,
			'currencyCode'      => $account->getPlanCurrency(),
			'shipBeforeDate'    => $today->format('Y-m-d'),
			'skinCode'          => $this->skin,
			'merchantAccount'   => $this->merchantAccount,
			'sessionValidity'   => $today->modify('+3 hours')->format(DATE_ATOM),
			'shopperEmail'      => $account->getEmail(),
			'shopperReference'  => $account->getId(),
			'recurringContract' => 'RECURRING',
			'resURL'            => $returnUrl,
			'allowedMethods'    => 'mc,visa,amex',
			'skipSelection'		=> 'true'
		);

		$parameters['merchantSig'] = $this->signature($parameters);

		return new RedirectResponse('https://' . $this->platform . '.adyen.com/hpp/select.shtml?' . http_build_query($parameters));
	}

	/**
	 * @param Account $account
	 * @param  $returnUrl
	 * @return Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function update(Account $account, $returnUrl)
	{
		$paymentAmount = 50;

		$transaction = new $this->entities['transaction'];
		$transaction->setAccount($account);
		$transaction->setType('update');
		$transaction->setAmount($paymentAmount);
		$transaction->setCurrency($account->getPlanCurrency());

		$this->em->persist($transaction);
		$this->em->flush();

		$today = new \DateTime();
		$parameters = array(
			'merchantReference' => 'Update ' . $transaction->getId(),
			'paymentAmount'     => $paymentAmount,
			'currencyCode'      => $account->getPlanCurrency(),
			'shipBeforeDate'    => $today->format('Y-m-d'),
			'skinCode'          => $this->skin,
			'merchantAccount'   => $this->merchantAccount,
			'sessionValidity'   => $today->modify('+3 hours')->format(DATE_ATOM),
			'shopperEmail'      => $account->getEmail(),
			'shopperReference'  => $account->getId(),
			'recurringContract' => 'RECURRING',
			'resURL'            => $returnUrl,
			'allowedMethods'    => 'mc,visa,amex',
			'skipSelection'		=> 'true'
		);

		$parameters['merchantSig'] = $this->signature($parameters);

		return new RedirectResponse('https://' . $this->platform . '.adyen.com/hpp/select.shtml?' . http_build_query($parameters));
	}

	protected function getSoapClient($type)
	{
		ini_set("soap.wsdl_cache_enabled", "0");

		return new \SoapClient(__DIR__.'/../Resources/wsdl/' . $this->platform . '/' . $type . '.wsdl', array(
			'login'         => $this->webservice['username'],
			'password'      => $this->webservice['password'],
			'soap_version'  => SOAP_1_1,
			'style'         => SOAP_DOCUMENT,
			'encoding'      => SOAP_LITERAL,
			'location'      => 'https://pal-' . $this->platform . '.adyen.com/pal/servlet/soap/' . $type,
			'trace' => 1
		));
	}

	protected function modification($type, array $modificationRequest)
	{
		$this->error = null;

		$client = $this->getSoapClient('Payment');
		try
		{
			return $client->__soapCall($type, array(
				$type => array(
					'modificationRequest' => $modificationRequest
				)
			));
		}
		catch(\SoapFault $exception)
		{
			$this->error = $exception->getMessage();

			return false;
		}
	}

	public function cancel(Transaction $transaction)
	{
		$this->error = null;

		if($transaction->isCancelled())
			return true;

		$result = $this->modification('cancel', array(
			'merchantAccount' => $this->merchantAccount,
			'originalReference' => $transaction->getReference()
		));

		if($result->cancelResult && $result->cancelResult->response == '[cancel-received]')
		{
			$transaction->isCancelled(true);
			$this->em->persist($transaction);

			return true;
		}

		return false;
	}

	public function disable(Account $account, $recurringReference = null)
	{
		$this->error = null;

		$client = $this->getSoapClient('Recurring');
		try
		{
			$result = $client->disable(array(
				'request' => array(
					'merchantAccount'           => $this->merchantAccount,
					'shopperReference'          => $account->getId(),
					'recurringDetailReference'  => $recurringReference
				)
			));

			if($result->result && $result->result->response == '[detail-successfully-disabled]')
				return true;
			elseif($result->result && $result->result->response == '[all-details-successfully-disabled]')
			{
				$account->setRecurringReference(null);
				$account->hasRecurringSetup(false);
				$account->setCardHolder(null);
				$account->setCardNumber(null);
				$account->setCardExpiryMonth(null);
				$account->setCardExpiryYear(null);

				$this->em->persist($account);
				$this->em->flush();

				return true;
			}

			$this->error = print_r($result, true);

			return false;
		}
		catch(\SoapFault $exception)
		{
			$this->error = $exception->getMessage();

			return false;
		}
	}

	public function charge(Account $account)
	{
		$this->error = null;

		$client = $this->getSoapClient('Payment');
		try
		{
			$plan = $account->getPlan();
			$paymentAmount = $this->priceToCents($plan->getPrice($account->getPlanCurrency()));

			$transaction = new $this->entities['transaction'];
			$transaction->setAccount($account);
			$transaction->setType('recurring');
			$transaction->setAmount($paymentAmount);
			$transaction->setCurrency($account->getPlanCurrency());

			$this->em->persist($transaction);

			$account->hasChargePending(true);
			$this->em->persist($account);

			$this->em->flush();

			$result = $client->authorise(array(
				'paymentRequest' => array(
					'selectedRecurringDetailReference' => $account->getRecurringReference(),
					'recurring' => array(
						'contract' => 'RECURRING'
					),
					"amount" => array(
						"value" => $paymentAmount,
						"currency" => $account->getPlanCurrency()
					),
					'merchantAccount' => $this->merchantAccount,
					'reference' => 'Recurring ' . $transaction->getId(),
					'shopperEmail' => $account->getEmail(),
					'shopperReference' => $account->getId(),
					'shopperInteraction' => 'ContAuth',
				)
			));

			$chargeEvent = new ChargeEvent(
	            $transaction,
	            $result->paymentResult->resultCode == 'Authorised',
	            $result
            );
            $this->dispatcher->dispatch('adyen.charge', $chargeEvent);

			return $result->paymentResult->resultCode;
		}
		catch(\SoapFault $exception)
		{
			$this->error = $exception->getMessage();

			$account->hasChargePending(false);
			$this->em->persist($account);

			$transaction->log($exception->getMessage());
			$transaction->log($client->__getLastRequest());
			$transaction->log($client->__getLastResponse());
			$this->em->persist($transaction);

			$this->em->flush();

			return false;
		}
	}

	public function getContracts(Account $account)
	{
		$this->error = null;
		
		$client = $this->getSoapClient('Recurring');
		try
		{
			$result = $client->listRecurringDetails(array(
				'request' => array(
					'merchantAccount'   => $this->merchantAccount,
					'shopperReference'  => $account->getId(),
					'recurring' => array(
						'contract' => 'RECURRING'
					)
				)
			));
			
			$array = $this->toArray($result);

			/**
			 * Fix the array when only one contract is found
			 */
			if(isset($array['result']['details']['RecurringDetail']['recurringDetailReference']))
				$array['result']['details']['RecurringDetail'] = array($array['result']['details']['RecurringDetail']);

			return $array['result']['details']['RecurringDetail'];
		}
		catch(\SoapFault $exception)
		{
			$this->error = $exception->getMessage();

			return false;
		}
	}

	public function loadContract(Account $account)
	{
		if($contracts = $this->getContracts($account))
		{
			$firstContract = array_shift($contracts);

			$account->setRecurringReference($firstContract['recurringDetailReference']);
			$account->hasRecurringSetup(true);
			$account->setCardHolder($firstContract['card']['holderName']);
			$account->setCardNumber($firstContract['card']['number']);
			$account->setCardExpiryMonth($firstContract['card']['expiryMonth']);
			$account->setCardExpiryYear($firstContract['card']['expiryYear']);

			$this->em->persist($account);
			$this->em->flush();

			return true;
		}

		return false;
	}

	public function processNotification(array $notification)
	{
		$merchantReference = preg_replace('/[^0-9]/Uis', '', $notification['merchantReference']);
		if($transaction = $this->em->getRepository($this->entities['transaction'])->find($merchantReference))
		{
			if($transaction->isProcessed() == false)
			{
				$transaction->isProcessed(true);
				$transaction->setReference($notification['pspReference']);

				if($notification['authResult'] == "AUTHORISATION" || $notification['authResult'] == "AUTHORISED")
				{
					switch($transaction->getType())
					{
						case "setup":
							$this->processSetupNotification($notification, $transaction);
							break;

						case "update":
							$this->processUpdateNotification($notification, $transaction);
							break;

						case "recurring":
							$this->processRecurringNotification($notification, $transaction);
							break;
					}
				}
				else $transaction->log($notification['authResult']);

				$this->em->persist($transaction);
				$this->em->flush();
			}

			return true;
		}

		return false;
	}

	protected function processSetupNotification(array $notification, Transaction $transaction)
	{
		$account = $transaction->getAccount();

		$account->extendPlan();

		$account->setRecurringReference($notification['pspReference']);
		$account->hasRecurringSetup(true);

		$this->em->persist($account);

		$this->loadContract($account);
	}

	protected function processUpdateNotification(array $notification, Transaction $transaction)
	{
		$account = $transaction->getAccount();

		/**
		 * Destroy the previous recurring contract
		 */
		if(!$this->disable($account, $account->getRecurringReference()))
			$transaction->log(sprintf('Disable old contract %s failed', $account->getRecurringReference()));

		/**
		 * Set the new recurring reference
		 */
		$account->setRecurringReference($notification['pspReference']);
		$this->em->persist($account);

		/**
		 * Cancel this 50 cent transaction
		 */
		if(!$this->cancel($transaction))
			$transaction->log('Cancel failed');

		/**
		 * Load the new contract
		 */
		$this->loadContract($account);

		/**
		 * Log errors
		 */
		$transaction->log($this->getError());
		$this->em->persist($transaction);
	}

	protected function processRecurringNotification(array $notification, Transaction $transaction)
	{
		$account = $transaction->getAccount();

		$account->extendPlan();

		$this->em->persist($account);
	}

	/**
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @return bool
	 */
	public function verifyAndProcessNotification(Request $request)
	{
		if($request->query->has('merchantReference') && $request->query->has('skinCode')
		&& $request->query->has('shopperLocale') && $request->query->has('paymentMethod')
		&& $request->query->has('authResult') && $request->query->has('pspReference') && $request->query->has('merchantSig'))
		{
			$parameters = array(
				'merchantReference' => $request->query->get('merchantReference'),
				'skinCode'          => $request->query->get('skinCode'),
				'shopperLocale'     => $request->query->get('shopperLocale'),
				'paymentMethod'     => $request->query->get('paymentMethod'),
				'authResult'        => $request->query->get('authResult'),
				'pspReference'      => $request->query->get('pspReference')
			);
			$expectedSignature = $this->signature($parameters);

			if($request->query->get('merchantSig') == $expectedSignature)
				return $this->processNotification($parameters);
		}

		return false;
	}

	public function getError()
	{
		return $this->error;
	}

	protected function signature(array $parameters)
	{
		$hmac = array();

		foreach(array('authResult', 'pspReference',
					  'paymentAmount', 'currencyCode', 'shipBeforeDate', 'merchantReference', 'skinCode', 'merchantAccount',
		              'sessionValidity', 'shopperEmail', 'shopperReference', 'recurringContract', 'allowedMethods', 'blockedMethods',
		              'shopperStatement', 'merchantReturnData', 'billingAddressType', 'deliveryAddressType', 'offset') AS $parameter)
		{
			if(isset($parameters[$parameter]))
				$hmac[] = $parameters[$parameter];
		}

		return base64_encode(hash_hmac('sha1', implode($hmac), $this->sharedSecret, true));
	}

	protected function priceToCents($price)
	{
		return $price * 100;
	}

	protected function toArray($d)
	{
		if (is_object($d)) {
			// Gets the properties of the given object
			// with get_object_vars function
			$d = get_object_vars($d);
		}

		if (is_array($d)) {
			/*
			* Return array converted to object
			* Using __FUNCTION__ (Magic constant)
			* for recursive call
			*/
			return array_map(array($this, 'toArray'), $d);
		}
		else {
			// Return array
			return $d;
		}
	}
}