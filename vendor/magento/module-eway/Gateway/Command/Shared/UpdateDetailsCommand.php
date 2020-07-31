<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Gateway\Command\Shared;

use Magento\Eway\Gateway\Helper;
use Magento\Payment\Gateway\Command;
use Magento\Sales\Model\Order\Payment;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Validator\ValidatorInterface;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Eway\Gateway\Validator\Shared\AccessCodeValidator;

/**
 * Class UpdateDetailsCommand
 */
class UpdateDetailsCommand implements CommandInterface
{
    /**
     * @var TransferFactoryInterface
     */
    private $transferFactory;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var HandlerInterface
     */
    private $handler;

    /**
     * Constructor
     *
     * @param TransferFactoryInterface $transferFactory
     * @param ClientInterface $client
     * @param HandlerInterface $handler
     * @param ValidatorInterface $validator
     */
    public function __construct(
        TransferFactoryInterface $transferFactory,
        ClientInterface $client,
        ValidatorInterface $validator,
        HandlerInterface $handler
    ) {
        $this->transferFactory = $transferFactory;
        $this->client = $client;
        $this->validator = $validator;
        $this->handler = $handler;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $commandSubject)
    {
        $accessCode = Helper\SubjectReader::readAccessCode($commandSubject);
        $paymentDO = SubjectReader::readPayment($commandSubject);

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();
        ContextHelper::assertOrderPayment($payment);

        $transferO = $this->transferFactory->create(
            [
                AccessCodeValidator::ACCESS_CODE => $accessCode
            ]
        );
        $response = $this->client->placeRequest($transferO);

        if ($this->validator) {
            $result = $this->validator->validate(
                array_merge(
                    $commandSubject,
                    [
                        'response' => $response,
                        'amount' => $payment->getOrder()->getTotalDue()
                    ]
                )
            );
            if (!$result->isValid()) {
                throw new CommandException(
                    __(implode("\n", $result->getFailsDescription()))
                );
            }
        }

        if ($this->handler) {
            $this->handler->handle($commandSubject, $response);
        }
    }
}
