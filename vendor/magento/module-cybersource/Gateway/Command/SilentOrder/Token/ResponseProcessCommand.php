<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Gateway\Command\SilentOrder\Token;

use Magento\Payment\Gateway\Command;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Validator\ValidatorInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\PaymentMethodManagementInterface;

class ResponseProcessCommand implements CommandInterface
{
    /**
     * @var HandlerInterface
     */
    private $handlerInterface;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var PaymentMethodManagementInterface
     */
    private $paymentManagement;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param ValidatorInterface $validator
     * @param HandlerInterface $handler
     * @param PaymentMethodManagementInterface $paymentManagement
     * @param Logger $logger
     */
    public function __construct(
        ValidatorInterface $validator,
        HandlerInterface $handler,
        PaymentMethodManagementInterface $paymentManagement,
        Logger $logger
    ) {
        $this->handlerInterface = $handler;
        $this->validator = $validator;
        $this->paymentManagement = $paymentManagement;
        $this->logger = $logger;
    }

    /**
     * Executes command basing on business object
     *
     * @param array $commandSubject
     * @return void
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    public function execute(array $commandSubject)
    {
        $response = SubjectReader::readResponse($commandSubject);

        $this->logger->debug(['payment_token_response' => $response]);

        $result = $this->validator->validate($commandSubject);
        if (!$result->isValid()) {
            throw new \LogicException();
        }

        $this->handlerInterface->handle($commandSubject, $response);

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = SubjectReader::readPayment($commandSubject);

        $this->paymentManagement->set(
            $paymentDO->getOrder()->getId(),
            $paymentDO->getPayment()
        );
    }
}
