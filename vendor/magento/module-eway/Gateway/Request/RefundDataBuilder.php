<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class RefundDataBuilder
 */
class RefundDataBuilder implements BuilderInterface
{
    /**
     * Refund block name
     */
    const REFUND = 'Refund';

    /**
     * The amount of the transaction in the lowest denomination for the currency
     * (e.g. a $27.00 transaction would have a TotalAmount value of ‘2700’).
     */
    const TOTAL_AMOUNT = 'TotalAmount';

    /**
     * The merchant’s invoice number for this transaction
     */
    const INVOICE_NUMBER = 'InvoiceNumber';

    /**
     * A description of the refund that the customer is making
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
     */
    const CURRENCY_CODE = 'CurrencyCode';

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        return [
            self::REFUND => [
                self::TOTAL_AMOUNT => sprintf('%.2F', SubjectReader::readAmount($buildSubject)) * 100
            ]
        ];
    }
}
