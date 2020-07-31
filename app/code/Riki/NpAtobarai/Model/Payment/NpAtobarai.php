<?php

namespace Riki\NpAtobarai\Model\Payment;

class NpAtobarai extends \Magento\Payment\Model\Method\AbstractMethod
{
    const PAYMENT_METHOD_NP_ATOBARAI_CODE = 'npatobarai';

    /**
     * @var string
     */
    protected $_code = self::PAYMENT_METHOD_NP_ATOBARAI_CODE;

    /**
     * @var bool
     */
    protected $_isOffline = true;
}
