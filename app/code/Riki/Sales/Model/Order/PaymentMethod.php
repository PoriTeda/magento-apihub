<?php
namespace Riki\Sales\Model\Order;
class PaymentMethod
{
    const PAYMENT_METHOD_COD = 'cashondelivery';
    const PAYMENT_METHOD_PAYGENT = 'paygent';
    const PAYMENT_METHOD_CVS = 'cvspayment';
    const PAYMENT_METHOD_INVOICED = 'invoicedbasedpayment';
    const PAYMENT_METHOD_FREE = 'free';
    const PAYMENT_METHOD_NPATOBARAI = 'npatobarai';
}
