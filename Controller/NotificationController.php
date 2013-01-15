<?php

namespace Sparkling\AdyenBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class NotificationController extends Controller
{
    public function serverAction()
    {
        ini_set("soap.wsdl_cache_enabled", "0");

        $server = new \SoapServer(__DIR__.'/../Resources/wsdl/test/Notification.wsdl');
        $server->setObject($this->get('adyen.notification'));

        $response = new Response();

        ob_start();
        $server->handle();
        $content = @ob_get_clean();

        if ($content) {
            $response->headers->set('Content-Type', 'text/xml; charset=ISO-8859-1');
            $response->setContent($content);
        } else $response->setContent("Adyen SOAP Notification server");

        return $response;
    }
}
