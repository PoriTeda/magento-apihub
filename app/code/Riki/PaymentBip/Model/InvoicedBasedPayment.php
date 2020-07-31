<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\PaymentBip\Model;



/**
 * Pay In Store payment method model
 */
class InvoicedBasedPayment extends \Magento\Payment\Model\Method\AbstractMethod
{

    const PAYMENT_CODE = 'invoicedbasedpayment';

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'invoicedbasedpayment';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;




}
