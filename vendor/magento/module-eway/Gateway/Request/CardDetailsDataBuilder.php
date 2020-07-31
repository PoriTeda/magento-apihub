<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Gateway\Request;

use Magento\Eway\Observer\DataAssignObserver;
use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Data\AddressAdapterInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;

/**
 * Class CardDetailsDataBuilder
 */
class CardDetailsDataBuilder implements BuilderInterface
{
    /**
     * CardDetails block name
     */
    const CARD_DETAILS = 'CardDetails';

    /**
     * The name of the card holder
     */
    const NAME = 'Name';

    /**
     * The card number that is to be processed for this transaction.
     * (Not required when processing using an existing CustomerTokenID with TokenPayment method).
     * This should be the encrypted value if using Client Side Encryption.
     *
     * @link https://eway.io/api-v3/#client-side-encryption
     */
    const NUMBER = 'Number';

    /**
     * The month that the card expires.
     * (Not required when processing using an existing Customer TokenID with TokenPayment method)
     */
    const EXPIRY_MONTH = 'ExpiryMonth';

    /**
     * The year that the card expires.
     * (Not required when processing using an existing CustomerTokenID with TokenPayment method)
     */
    const EXPIRY_YEAR = 'ExpiryYear';

    /**
     * The month that the card is valid from
     *
     * WARNING:
     * Applies to UK only
     *
     * @deprecated unused
     */
    const START_MONTH = 'StartMonth';

    /**
     * The year that the card is valid from
     *
     * WARNING:
     * Applies to UK only
     *
     * @deprecated unused
     */
    const START_YEAR = 'StartYear';

    /**
     * The card’s issue number
     *
     * @deprecated unused
     */
    const ISSUE_NUMBER = 'IssueNumber';

    /**
     * The Card Verification Number.
     * This should be the encrypted value if using Client Side Encryption.
     *
     * WARNING:
     * Required if the TransactionTye is Purchase
     *
     * @link https://eway.io/api-v3/#client-side-encryption
     */
    const CVN = 'CVN';

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);

        $order = $paymentDO->getOrder();
        $billingAddress = $order->getBillingAddress();

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $paymentDO->getPayment();
        ContextHelper::assertOrderPayment($payment);
        $data = $payment->getAdditionalInformation();

        return [
            CustomerDataBuilder::CUSTOMER => [
                self::CARD_DETAILS => [
                    self::NAME => $this->getName($billingAddress),
                    self::NUMBER => $data[DataAssignObserver::CC_NUMBER],
                    self::EXPIRY_MONTH => $this->formatMonth($data[OrderPaymentInterface::CC_EXP_MONTH]),
                    self::EXPIRY_YEAR => $this->formatYear($data[OrderPaymentInterface::CC_EXP_YEAR]),
                    self::CVN => $data[DataAssignObserver::CC_CID]
                ]
            ]
        ];
    }

    /**
     * Get full customer name
     *
     * @param AddressAdapterInterface $billingAddress
     * @return string
     */
    private function getName(AddressAdapterInterface $billingAddress)
    {
        return $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname();
    }

    /**
     * @param string $month
     * @return null|string
     */
    private function formatMonth($month)
    {
        return !empty($month) ? sprintf('%02d', $month) : null;
    }

    /**
     * @param string $year
     * @return null|string
     */
    private function formatYear($year)
    {
        return !empty($year) ? substr($year, -2, 2) : null;
    }
}
