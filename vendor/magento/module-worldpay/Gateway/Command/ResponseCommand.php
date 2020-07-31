<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Worldpay\Gateway\Command;

use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\ValidatorInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Worldpay\Gateway\Request\HtmlRedirect\OrderDataBuilder;
use Magento\Worldpay\Gateway\Validator\DecisionValidator;

/**
 * Class ResponseCommand
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ResponseCommand implements CommandInterface
{
    const ACCEPT_COMMAND = 'accept_command';

    const CANCEL_COMMAND = 'cancel_command';

    /**
     * Transaction result codes map onto commands
     *
     * @var array
     */
    static private $commandsMap = [
        'C' => self::CANCEL_COMMAND,
        'Y' => self::ACCEPT_COMMAND
    ];

    /**
     * @var CommandPoolInterface
     */
    private $commandPool;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var PaymentDataObjectFactory
     */
    private $paymentDataObjectFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param CommandPoolInterface $commandPool
     * @param ValidatorInterface $validator
     * @param OrderRepositoryInterface $orderRepository
     * @param PaymentDataObjectFactory $paymentDataObjectFactory
     * @param Logger $logger
     */
    public function __construct(
        CommandPoolInterface $commandPool,
        ValidatorInterface $validator,
        OrderRepositoryInterface $orderRepository,
        PaymentDataObjectFactory $paymentDataObjectFactory,
        Logger $logger
    ) {
        $this->commandPool = $commandPool;
        $this->validator = $validator;
        $this->orderRepository = $orderRepository;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->logger = $logger;
    }

    /**
     * @param array $commandSubject
     *
     * @return void
     * @throws CommandException
     */
    public function execute(array $commandSubject)
    {
        $this->logger->debug($commandSubject);

        $response = SubjectReader::readResponse($commandSubject);
        $result = $this->validator->validate($commandSubject);

        if (!$result->isValid()) {
            throw new CommandException(
                $result->getFailsDescription()
                ? __(implode(', ', $result->getFailsDescription()))
                : __('Gateway response is not valid.')
            );
        }

        $order = $this->orderRepository->get((int)$response[OrderDataBuilder::ORDER_ID]);

        $actionCommandSubject = [
            'response' => $response,
            'payment' => $this->paymentDataObjectFactory->create(
                $order->getPayment()
            )
        ];

        $command = $this->commandPool->get(
            self::$commandsMap[
            $response['transStatus']
            ]
        );

        $command->execute($actionCommandSubject);
    }
}
