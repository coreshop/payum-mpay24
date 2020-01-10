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

namespace CoreShop\Payum\Mpay24;

use CoreShop\Payum\Mpay24\Action\CaptureOffSiteAction;
use CoreShop\Payum\Mpay24\Action\ConvertPaymentAction;
use CoreShop\Payum\Mpay24\Action\NotifyAction;
use CoreShop\Payum\Mpay24\Action\StatusAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

class Mpay24GatewayFactory extends GatewayFactory
{
    /**
     * {@inheritDoc}
     */
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults([
            'payum.factory_name' => 'mpay24',
            'payum.factory_title' => 'mpay24',
            'payum.action.capture' => new CaptureOffSiteAction(),
            'payum.action.notify' => new NotifyAction(),
            'payum.action.status' => new StatusAction(),
            'payum.action.convert_payment' => new ConvertPaymentAction(),
        ]);

        if (false == $config['payum.api']) {
            $config['payum.default_options'] = [
                'merchantId' => '',
                'password' => '',
                'test' => true,
                'paymentType' => '',
                'brand' => ''
            ];
            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = ['merchantId', 'password'];

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                return new Api(
                    $config['merchantId'],
                    $config['password'],
                    $config['test'],
                    $config['paymentType'],
                    $config['brand']
                );
            };
        }
    }
}
