<?php

namespace Sparkling\AdyenBundle\Service;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Sparkling\AdyenBundle\Entity\Subscription;
use Sparkling\AdyenBundle\Entity\Plan;
use Sparkling\AdyenBundle\Entity\Transaction;
use Sparkling\AdyenBundle\Event\ChargeEvent;
use Sparkling\AdyenBundle\Event\PriceEvent;
use Sparkling\AdyenBundle\Event\CurrencyEvent;

class AdyenService
{
    protected $platform;
    protected $merchantAccount;
    protected $skin;
    protected $sharedSecret;
    protected $currency;
    protected $entities = array();
    protected $webservice = array();
    protected $updateChargeAmount = 2; // 2 cent for authorisation
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
     * @param  \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
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
     * @param Subscription $subscription
     * @param Plan         $plan
     * @param  $returnUrl
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function setup(Subscription $subscription, Plan $plan, $returnUrl)
    {
        /**
         * @var \Sparkling\AdyenBundle\Entity\Transaction $transaction
         */
        $transaction = new $this->entities['transaction'];

        /**
         * Fire up a CurrencyEvent to allow modification of the currency by developers
         */
        $currencyEvent = new CurrencyEvent($subscription, $plan, $this->currency);
        $this->dispatcher->dispatch('adyen.currency', $currencyEvent);

        $today = new \DateTime();
        if ($subscription->getPlanExpiresAt() <= $today) {
            /**
             * When the plan is already expired we have to charge the first month directly
             *
             * Fire up a PriceEvent to allow modification of the price and tax by developers
             */
            $priceEvent = new PriceEvent($subscription, $plan, $currencyEvent->getCurrency());
            $this->dispatcher->dispatch('adyen.price', $priceEvent);

            $paymentAmount = $priceEvent->getCents($applyDiscount = true);

            $transaction->setAmount($paymentAmount);
            $transaction->setDiscount($priceEvent->getDiscount());

            $subscription->hasChargePending(true);
            $this->em->persist($subscription);
        } else {
            /**
             * Set the paymentAmount to 2 cent and cancel it on a successful authorisation
             */

            $paymentAmount = $this->updateChargeAmount;

            $transaction->setAmount($paymentAmount);
        }

        $transaction->setSubscription($subscription);
        $transaction->setPlan($plan);
        $transaction->setType('setup');
        $transaction->setCurrency($currencyEvent->getCurrency());
        $this->em->persist($transaction);

        $this->em->flush();

        $today = new \DateTime();
        $parameters = array(
            'merchantReference' => 'Setup ' . $transaction->getId(),
            'paymentAmount'     => $paymentAmount,
            'currencyCode'      => $transaction->getCurrency(),
            'shipBeforeDate'    => $today->format('Y-m-d'),
            'skinCode'          => $this->skin,
            'merchantAccount'   => $this->merchantAccount,
            'sessionValidity'   => $today->modify('+3 hours')->format(DATE_ATOM),
            'shopperEmail'      => $subscription->getEmail(),
            'shopperReference'  => $subscription->getId(),
            'recurringContract' => 'RECURRING',
            'resURL'            => $returnUrl,
            'allowedMethods'    => 'mc,visa,amex',
            'skipSelection'		=> 'true'
        );

        $parameters['merchantSig'] = $this->signature($parameters);

        return new RedirectResponse('https://' . $this->platform . '.adyen.com/hpp/select.shtml?' . http_build_query($parameters));
    }

    /**
     * @param Subscription $subscription
     * @param  $returnUrl
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function update(Subscription $subscription, $returnUrl)
    {
        $paymentAmount = $this->updateChargeAmount;

        /**
         * Fire up a CurrencyEvent to allow modification of the currency by developers
         */
        $currencyEvent = new CurrencyEvent($subscription, $subscription->getPlan(), $this->currency);
        $this->dispatcher->dispatch('adyen.currency', $currencyEvent);

        /**
         * @var \Sparkling\AdyenBundle\Entity\Transaction $transaction
         */
        $transaction = new $this->entities['transaction'];
        $transaction->setSubscription($subscription);
        $transaction->setType('update');
        $transaction->setAmount($paymentAmount);
        $transaction->setCurrency($currencyEvent->getCurrency());

        $this->em->persist($transaction);
        $this->em->flush();

        $today = new \DateTime();
        $parameters = array(
            'merchantReference' => 'Update ' . $transaction->getId(),
            'paymentAmount'     => $paymentAmount,
            'currencyCode'      => $transaction->getCurrency(),
            'shipBeforeDate'    => $today->format('Y-m-d'),
            'skinCode'          => $this->skin,
            'merchantAccount'   => $this->merchantAccount,
            'sessionValidity'   => $today->modify('+3 hours')->format(DATE_ATOM),
            'shopperEmail'      => $subscription->getEmail(),
            'shopperReference'  => $subscription->getId(),
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
        try {
            return $client->__soapCall($type, array(
                $type => array(
                    'modificationRequest' => $modificationRequest
                )
            ));
        } catch (\SoapFault $exception) {
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

        if ($result->cancelResult && $result->cancelResult->response == '[cancel-received]') {
            $transaction->isCancelled(true);
            $this->em->persist($transaction);

            return true;
        }

        return false;
    }

    public function disable(Subscription $subscription, $recurringReference = null)
    {
        $remove = false;
        $this->error = null;

        $client = $this->getSoapClient('Recurring');
        try {
            $result = $client->disable(array(
                'request' => array(
                    'merchantAccount'           => $this->merchantAccount,
                    'shopperReference'          => $subscription->getId(),
                    'recurringDetailReference'  => $recurringReference
                )
            ));

            if ($result->result && ($result->result->response == '[detail-successfully-disabled]' || $result->result->response == '[all-details-successfully-disabled]')) {
                if ($recurringReference === null || $subscription->getRecurringReference() == $recurringReference) {
                    $remove = true;
                }

                return true;
            } else {
                $this->error = print_r($result, true);

                return false;
            }
        } catch (\SoapFault $exception) {
            if ($exception->getMessage() !== "validation 800 Contract not found") {
                $this->error = $exception->getMessage();

                return false;
            } else {
                $remove = true;
            }
        }

        if ($remove === true) {
            $subscription->setRecurringReference(null);
            $subscription->hasRecurringSetup(false);
            $subscription->setCardHolder(null);
            $subscription->setCardNumber(null);
            $subscription->setCardExpiryMonth(null);
            $subscription->setCardExpiryYear(null);

            $this->em->persist($subscription);
            $this->em->flush();
        }
    }

    /**
     * @param  \Sparkling\AdyenBundle\Entity\Subscription     $subscription
     * @return bool|\Sparkling\AdyenBundle\Entity\Transaction
     */
    public function charge(Subscription $subscription)
    {
        $this->error = null;

        /**
         * Fire up a CurrencyEvent to allow modification of the currency by developers
         */
        $currencyEvent = new CurrencyEvent($subscription, $subscription->getPlan(), $this->currency);
        $this->dispatcher->dispatch('adyen.currency', $currencyEvent);

        $client = $this->getSoapClient('Payment');
        try {
            $plan = $subscription->getPlan();

            /**
             * Fire up a PriceEvent to allow modification of the price and tax by developers
             */
            $priceEvent = new PriceEvent($subscription, $plan, $currencyEvent->getCurrency());
            $this->dispatcher->dispatch('adyen.price', $priceEvent);

            /**
             * @var \Sparkling\AdyenBundle\Entity\Transaction $transaction
             */
            $transaction = new $this->entities['transaction'];
            $transaction->setSubscription($subscription);
            $transaction->setPlan($subscription->getPlan());
            $transaction->setType('recurring');
            $transaction->setAmount($priceEvent->getCents());
            $transaction->setTax($priceEvent->getTax());
            $transaction->setDiscount($priceEvent->getDiscount());
            $transaction->setCurrency($priceEvent->getCurrency());

            $this->em->persist($transaction);

            $subscription->hasChargePending(true);
            $this->em->persist($subscription);

            $this->em->flush();

            if ($priceEvent->getDiscount() == 100) {
                /**
                 * No need to charge as the discount is 100% (free)
                 */
                $transaction->isProcessed(true);
                $transaction->setReference('free');
                $this->em->persist($transaction);

                $this->processRecurringNotification(array(), $transaction);

                $this->em->flush();

                return $transaction;
            } else {
                /**
                 * Charge it
                 */
                $result = $client->authorise(array(
                    'paymentRequest' => array(
                        'selectedRecurringDetailReference' => $subscription->getRecurringReference(),
                        'recurring' => array(
                            'contract' => 'RECURRING'
                        ),
                        "amount" => array(
                            "value" => $priceEvent->getCents($applyDiscount = true),
                            "currency" => $priceEvent->getCurrency()
                        ),
                        'merchantAccount' => $this->merchantAccount,
                        'reference' => 'Recurring ' . $transaction->getId(),
                        'shopperEmail' => $subscription->getEmail(),
                        'shopperReference' => $subscription->getId(),
                        'shopperInteraction' => 'ContAuth',
                    )
                ));

                $chargeEvent = new ChargeEvent(
                    $transaction,
                    $result->paymentResult->resultCode == 'Authorised'
                );
                $this->dispatcher->dispatch('adyen.charge', $chargeEvent);

                $this->em->flush();

                return $transaction;
            }
        } catch (\SoapFault $exception) {
            $this->error = $exception->getMessage();

            $subscription->hasChargePending(false);
            $this->em->persist($subscription);

            /**
             * @todo This is weird.... What if the transaction is not yet created??
             */
            $transaction->log($exception->getMessage());
            $transaction->log($client->__getLastRequest());
            $transaction->log($client->__getLastResponse());
            $this->em->persist($transaction);

            $this->em->flush();

            return false;
        }
    }

    public function getContracts(Subscription $subscription)
    {
        $this->error = null;

        $client = $this->getSoapClient('Recurring');
        try {
            $result = $client->listRecurringDetails(array(
                'request' => array(
                    'merchantAccount'   => $this->merchantAccount,
                    'shopperReference'  => $subscription->getId(),
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

            $contracts = array();
            if (isset($array['result']['details']['RecurringDetail'])) {
                foreach ($array['result']['details']['RecurringDetail'] AS $key => $details) {
                    $date = new \DateTime($details['creationDate']);
                    $contracts[$date->format('U')] = $details;
                }

                krsort($contracts);
            }

            return $contracts;
        } catch (\SoapFault $exception) {
            $this->error = $exception->getMessage();

            return false;
        }
    }

    public function loadContract(Subscription $subscription)
    {
        if ($contracts = $this->getContracts($subscription)) {
            $firstContract = array_shift($contracts);

            $subscription->setRecurringReference($firstContract['recurringDetailReference']);
            $subscription->hasRecurringSetup(true);
            $subscription->setCardHolder($firstContract['card']['holderName']);
            $subscription->setCardNumber($firstContract['card']['number']);
            $subscription->setCardExpiryMonth($firstContract['card']['expiryMonth']);
            $subscription->setCardExpiryYear($firstContract['card']['expiryYear']);

            $this->em->persist($subscription);
            $this->em->flush();

            return true;
        }

        return false;
    }

    public function processNotification(array $notification)
    {
        $merchantReference = preg_replace('/[^0-9]/Uis', '', $notification['merchantReference']);
        if ($transaction = $this->em->getRepository($this->entities['transaction'])->find($merchantReference)) {
            if ($transaction->isProcessed() == false) {
                $transaction->isProcessed(true);
                $transaction->setReference($notification['pspReference']);

                if ($notification['authResult'] == "AUTHORISED" || $notification['authResult'] == "AUTHORISATION") {
                    switch ($transaction->getType()) {
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

                    $this->em->persist($transaction);
                    $this->em->flush();
                } else {
                    $transaction->log("Failure: " . $notification['authResult']);

                    $this->em->persist($transaction);
                    $this->em->flush();

                    return false;
                }
            } else {
                /**
                 * When this transaction is already processed, just grab the latest contracts
                 */
                $this->loadContract($transaction->getSubscription());
            }

            return true;
        }

        return false;
    }

    protected function processSetupNotification(array $notification, Transaction $transaction)
    {
        $subscription = $transaction->getSubscription();

        $subscription->hasRecurringSetup(true);
        $subscription->isExpired(false);
        $subscription->isTrial(false);

        if ($transaction->getPlan() !== null) {
            $subscription->setPlan($transaction->getPlan());
        }

        $this->em->persist($subscription);

        /**
         * There can be 2 different types of setupTransactions:
         *   1. 2 cent recurring authorisation
         *   2. Charge for the first month
         */
        if ($transaction->getAmount() == $this->updateChargeAmount) {
            /**
             * This is just a authorisation to get the recurringContract running
             *
             * So we are just going to cancel the transaction at Adyen
             */
            if(!$this->cancel($transaction))
                $transaction->log('Cancel failed');
        } else {
            /**
             * This is the payment for the first month
             */
            $subscription->extendPlan();

            /**
             * Fire a charge event
             */
            $this->dispatcher->dispatch('adyen.charge', new ChargeEvent($transaction, true));
        }

        /**
         * Load the new contract
         */
        $this->loadContract($subscription);

        /**
         * Log errors
         */
        $transaction->log($this->getError());
        $this->em->persist($transaction);
    }

    protected function processUpdateNotification(array $notification, Transaction $transaction)
    {
        $subscription = $transaction->getSubscription();

        /**
         * Destroy the previous recurring contract
         */
        if(!$this->disable($subscription, $subscription->getRecurringReference()))
            $transaction->log(sprintf('Disable old contract %s failed', $subscription->getRecurringReference()));

        /**
         * Cancel this 2 cent transaction
         */
        if(!$this->cancel($transaction))
            $transaction->log('Cancel failed');

        /**
         * Load the new contract
         */
        $this->loadContract($subscription);

        /**
         * Log errors
         */
        $transaction->log($this->getError());
        $this->em->persist($transaction);
    }

    protected function processRecurringNotification(array $notification, Transaction $transaction)
    {
        $subscription = $transaction->getSubscription();

        $subscription->extendPlan();

        $this->em->persist($subscription);
    }

    /**
     * @param  \Symfony\Component\HttpFoundation\Request $request
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
        } else {
            // Return array
            return $d;
        }
    }
}
