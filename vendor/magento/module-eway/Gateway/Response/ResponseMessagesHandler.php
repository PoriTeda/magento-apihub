<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Gateway\Response;

use Magento\Sales\Model\Order\Payment;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Eway\Gateway\Validator\Direct\ResponseValidator;

/**
 * Class ResponseMessagesHandler
 */
class ResponseMessagesHandler implements HandlerInterface
{
    /**
     * Pattern search for message types
     */
    const FRAUD_MESSAGES = "/^F.*/";
    const APPROVED_MESSAGES = "/^A.*/";

    /**
     * Codes of messages
     *
     * @var array
     */
    protected $messagesCodes = [
        'F7000', 'F7001', 'F7002', 'F7003', 'F7004',
        'F7005', 'F7006', 'F7007', 'F7008', 'F7009',
        'F9010', 'F9011', 'F9012', 'F9013', 'F9014',
        'F9015', 'F9016', 'F9017', 'F9018', 'F9019',
        'F9020', 'F9021', 'F9022', 'F9023', 'F9024',
        'F9025', 'F9026', 'F9027', 'F9028', 'F9029',
        'F9030', 'F9031', 'F9032', 'F9033', 'F9034',
        'F9037', 'F9050', 'A0000', 'A2000', 'A2008',
        'A2010', 'A2011', 'A2016',
    ];

    /**
     * @inheritdoc
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);
        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();
        ContextHelper::assertOrderPayment($payment);

        $messages = str_replace(' ', '', $response[ResponseValidator::RESPONSE_MESSAGE]);
        $messages = explode(',', $messages);

        $fraudMessages = $this->getMessages(self::FRAUD_MESSAGES, $messages);
        if (!empty($fraudMessages)) {
            $payment->setIsTransactionPending(false);
            $payment->setIsFraudDetected(true);

            $payment->setAdditionalInformation('fraud_messages', $fraudMessages);
        }

        $payment->setAdditionalInformation(
            'approve_messages',
            $this->getMessages(self::APPROVED_MESSAGES, $messages)
        );
    }

    /**
     * Getting messages
     *
     * @param string $pattern
     * @param array $messages
     * @return array
     */
    protected function getMessages($pattern, array $messages)
    {
        $matches = preg_grep($pattern, $messages);
        $resultMessages = [];
        if (!empty($matches)) {
            $resultMessages = array_intersect($this->messagesCodes, $matches);
        }

        return $resultMessages;
    }
}
