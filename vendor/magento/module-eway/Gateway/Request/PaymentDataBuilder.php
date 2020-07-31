<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class PaymentDataBuilder
 */
class PaymentDataBuilder implements BuilderInterface
{
    /**
     * Payment block name
     */
    const PAYMENT = 'Payment';

    /**
     * The amount of the transaction in the lowest denomination for the currency
     * (e.g. a $27.00 transaction would have a TotalAmount value of 2700).
     *
     * WARNING:
     * The value of this field must be 0 for the CreateTokenCustomer and UpdateTokenCustomer methods
     * This field is required when the Action is ProcessPayment or TokenPayment
     */
    const TOTAL_AMOUNT = 'TotalAmount';

    /**
     * The merchant’s invoice number for this transaction
     */
    const INVOICE_NUMBER = 'InvoiceNumber';

    /**
     * A description of the purchase that the customer is making
     */
    const INVOICE_DESCRIPTION = 'InvoiceDescription';

    /**
     * The merchant’s reference number for this transaction
     */
    const INVOICE_REFERENCE = 'InvoiceReference';

    /**
     * The ISO 4217 3 character code that represents the currency that this transaction is to be processed in.
     * If no value for this field is provided, the merchant’s default currency is used. This should be in uppercase.
     * e.g. Australian Dollars = AUD
     *
     * @link http://en.wikipedia.org/wiki/ISO_4217
     */
    const CURRENCY_CODE = 'CurrencyCode';

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);

        $order = $paymentDO->getOrder();

        return [
            self::PAYMENT => [
                self::TOTAL_AMOUNT => sprintf('%.2F', SubjectReader::readAmount($buildSubject)) * 100,
                self::CURRENCY_CODE => $order->getCurrencyCode()
            ]
        ];
    }
}
