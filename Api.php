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
     * @param $merchantId
     * @param string $password
     * @param bool $test
     */
    public function __construct($merchantId, string $password, bool $test)
    {
        $this->merchantId = $merchantId;
        $this->password = $password;
        $this->test = $test;
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
}
