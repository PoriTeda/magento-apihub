<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Gateway\Response\Direct;

use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Eway\Model\Adminhtml\Source\Cctype;
use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Eway\Gateway\Validator\Direct\ResponseValidator;

/**
 * Class CardDetailsHandler
 */
class CardDetailsHandler implements HandlerInterface
{
    /**
     * Config
     *
     * @var Cctype
     */
    private $sourceCCtype;

    /**
     * Constructor
     *
     * @param Cctype $sourceCCtype
     */
    public function __construct(Cctype $sourceCCtype)
    {
        $this->sourceCCtype = $sourceCCtype;
    }

    /**
     * @inheritdoc
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();
        ContextHelper::assertOrderPayment($payment);

        $cardDetails = $response[ResponseValidator::CUSTOMER][ResponseValidator::CARD_DETAILS];

        $ccTypes = $this->sourceCCtype->getCcTypes();
        $payment->setAdditionalInformation(
            'cc_type',
            $ccTypes[$payment->getAdditionalInformation(OrderPaymentInterface::CC_TYPE)]
        );
        $payment->setAdditionalInformation(
            'card_number',
            'XXXX-' . substr($cardDetails[ResponseValidator::CARD_NUMBER], -4)
        );
        $payment->setAdditionalInformation(
            'card_expiry_date',
            sprintf(
                '%s/%s',
                $cardDetails[ResponseValidator::CARD_EXPIRY_MONTH],
                $cardDetails[ResponseValidator::CARD_EXPIRY_YEAR]
            )
        );
    }
}
