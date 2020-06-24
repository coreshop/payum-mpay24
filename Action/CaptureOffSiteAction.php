<?php
/**
 * CoreShop.
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2020 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
 * @license    https://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace CoreShop\Payum\Mpay24\Action;

use CoreShop\Payum\Mpay24\Api;
use Mpay24\Mpay24Order;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Request\Capture;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;

/**
 * @property Api $api
 */
class CaptureOffSiteAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface, GenericTokenFactoryAwareInterface
{
    use ApiAwareTrait;
    use GatewayAwareTrait;
    use GenericTokenFactoryAwareTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    /**
     * {@inheritDoc}
     *
     * @param Capture $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        $mpay24 = $this->api->getMpay24Api();
        $paymentType = $this->api->getPaymentType();
        $brand = $this->api->getBrand();

        $httpRequest = new GetHttpRequest();
        $this->gateway->execute($httpRequest);

        //we are back from postFinance site so we have to just update model.
        if (isset($httpRequest->query['TID'])) {
            $model->replace($httpRequest->query);
        } else {
            $notifyToken = $this->tokenFactory->createNotifyToken(
                $request->getToken()->getGatewayName(),
                $request->getToken()->getDetails()
            );
            $targetUrl = $request->getToken()->getTargetUrl();

            $mdxi = new Mpay24Order();
            $mdxi->Order->Tid = $model['tid'];

            if ($model['order']) {
                $order = ArrayObject::ensureArrayObject($model['order']);

                if ($order['language']) {
                    $mdxi->Order->TemplateSet->setLanguage($order['language']);
                }

                if ($order['cssName'] && $order['language']) {
                    $mdxi->Order->TemplateSet->setCSSName($order['cssName']);
                }
            }

            // set pre-selected payment type
            if (!empty($paymentType)) {
                $mdxi->Order->PaymentTypes->setEnable("true");
                $mdxi->Order->PaymentTypes->Payment(1)->setType($paymentType);

                // if defined set the brand
                if (!empty($brand)) {
                    $mdxi->Order->PaymentTypes->Payment(1)->setBrand($brand);
                }
            }

            if ($model['order']) {
                $order = ArrayObject::ensureArrayObject($model['order']);

                if ($order['logoStyle']) {
                    $mdxi->Order->setLogoStyle($order['logoStyle']);
                }

                if ($order['description']) {
                    $mdxi->Order->ShoppingCart->Description = $order['description'];
                }

                if ($order['items'] && is_array($order['items'])) {
                    foreach ($order['items'] as $index => $item) {
                        $item = ArrayObject::ensureArrayObject($item);

                        if ($item['number']) {
                            $mdxi->Order->ShoppingCart->Item($index)->Number = $item['number'];
                        }

                        if ($item['productNr']) {
                            $mdxi->Order->ShoppingCart->Item($index)->ProductNr = $item['productNr'];
                        }

                        if ($item['description']) {
                            $mdxi->Order->ShoppingCart->Item($index)->Description = $item['description'];
                        }

                        if ($item['package']) {
                            $mdxi->Order->ShoppingCart->Item($index)->Package = $item['package'];
                        }

                        if ($item['quantity']) {
                            $mdxi->Order->ShoppingCart->Item($index)->Quantity = $item['quantity'];
                        }

                        if ($item['itemPrice']) {
                            $mdxi->Order->ShoppingCart->Item($index)->ItemPrice = $item['itemPrice'];
                        }

                        if ($item['itemTax']) {
                            $mdxi->Order->ShoppingCart->Item($index)->ItemPrice->setTax($item['itemTax']);
                        }

                        if ($item['price']) {
                            $mdxi->Order->ShoppingCart->Item($index)->Price = $item['price'];
                        }
                    }
                }

                $mdxi->Order->Price = $model['price'];
                $mdxi->Order->Currency = $model['currency'];

                if ($order['customer'] && is_array($order['customer'])) {
                    $customer = ArrayObject::ensureArrayObject($order['customer']);

                    $mdxi->Order->Customer->setUseProfile("true");
                    $mdxi->Order->Customer->setId($customer['id']);

                    if ($customer['name']) {
                        $mdxi->Order->Customer = $customer['name'];
                    }

                    if ($customer['billingAddress'] && is_array($customer['billingAddress'])) {
                        $address = ArrayObject::ensureArrayObject($customer['billingAddress']);

                        $mdxi->Order->BillingAddr->setMode("ReadOnly");

                        if ($address['name']) {
                            $mdxi->Order->BillingAddr->Name = $address['name'];
                        }

                        if ($address['street']) {
                            $mdxi->Order->BillingAddr->Street = $address['street'];
                        }

                        if ($address['street2']) {
                            $mdxi->Order->BillingAddr->Street2 = $address['street2'];
                        }

                        if ($address['zip']) {
                            $mdxi->Order->BillingAddr->Zip = $address['zip'];
                        }

                        if ($address['city']) {
                            $mdxi->Order->BillingAddr->City = $address['city'];
                        }

                        if ($address['countryCode']) {
                            $mdxi->Order->BillingAddr->Country->setCode($address['countryCode']);
                        }

                        if ($address['email']) {
                            $mdxi->Order->BillingAddr->Email = $address['email'];
                        }
                    }
                }
            }

            $mdxi->Order->Price = $model['price'];
            $mdxi->Order->Currency = $model['currency'];
            $mdxi->Order->URL->Success = $targetUrl;
            $mdxi->Order->URL->Error = $targetUrl;
            $mdxi->Order->URL->Confirmation = $notifyToken->getTargetUrl();

            $result = $mpay24->paymentPage($mdxi);
            $redirect = $result->getLocation();

            throw new HttpRedirect(
                $redirect
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
