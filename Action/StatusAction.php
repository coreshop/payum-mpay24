<?php
/**
 * CoreShop.
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2017 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
 * @license    https://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace CoreShop\Payum\Mpay24\Action;

use CoreShop\Payum\Mpay24\Api;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetStatusInterface;

/**
 * @property Api $api
 */
class StatusAction implements ActionInterface, ApiAwareInterface
{
    use ApiAwareTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    /**
     * {@inheritDoc}
     *
     * @param GetStatusInterface $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = new ArrayObject($request->getModel());

        if ($model['tid']) {
            $status = $this->api->getMpay24Api()->paymentStatusByTid($model['tid']);

            if ($status->getStatus() === 'OK') {
                $model['STATUS'] = $status->getParam('STATUS');
            }
        }

        if (null === $model['STATUS']) {
            $request->markNew();
            return;
        }

        $status = $model['STATUS'];

        switch ($status) {
            case Api::STATE_RESERVED:
                $request->markAuthorized();
                break;
            case Api::STATE_CREDITED:
                $request->markCaptured();
                break;
            case Api::STATE_BILLED:
                $request->markCaptured();
                break;
            case Api::STATE_ERROR:
                $request->markFailed();
                break;
            case Api::STATE_REVERSED:
                $request->markPayedout();
                break;
            default:
                $request->markUnknown();
                break;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
