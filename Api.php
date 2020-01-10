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

use Mpay24\Mpay24;

final class Api
{
    /**
     * The amount was reserved but not settled/billed yet. The transaction was successful.
     */
    const STATE_RESERVED = 'RESERVED';

    /**
     * The amount was settled/billed. The transaction was successful.
     */
    const STATE_BILLED = 'BILLED';

    /**
     * The reserved amount was released. The transaction was canceled.
     */
    const STATE_REVERSED = 'REVERSED';

    /**
     * The amount will be refunded. The transaction was credited.
     */
    const STATE_CREDITED = 'CREDITED';

    /**
     * The transaction failed upon the last request. (e.g. wrong/invalid data, financial reasons, ...)
     */
    const STATE_ERROR = 'ERROR';

    /**
     * @var
     */
    private $merchantId;

    /**
     * @var string
     */
    private $password;

    /**
     * @var bool
     */
    private $test = true;

    /**
     * @var Mpay24
     */
    private $mpay24Api;
    /**
     * @var string
     */
    private $paymentType;
    /**
     * @var string
     */
    private $brand;

    /**
     * @param $merchantId
     * @param string $password
     * @param bool $test
     * @param string $paymentType
     * @param string $brand
     */
    public function __construct($merchantId, string $password, bool $test, string $paymentType = null, string $brand = null)
    {
        $this->merchantId = $merchantId;
        $this->password = $password;
        $this->test = $test;
        $this->paymentType = $paymentType;
        $this->brand = $brand;
    }

    /**
     * @return Mpay24
     */
    public function getMpay24Api()
    {
        if (is_null($this->mpay24Api)) {
            $this->mpay24Api = new Mpay24($this->merchantId, $this->password, $this->test);
        }

        return $this->mpay24Api;
    }

    /**
     * @return string
     */
    public function getPaymentType()
    {
        return $this->paymentType;
    }

    /**
     * @return string
     */
    public function getBrand()
    {
        return $this->brand;
    }
}
