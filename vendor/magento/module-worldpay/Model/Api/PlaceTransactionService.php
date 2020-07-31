<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Worldpay\Model\Api;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Worldpay\Gateway\Command\Form\BuildCommand;

/**
 * Class PlaceTransactionService
 */
class PlaceTransactionService
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var BuildCommand
     */
    private $command;

    /**
     * @var PaymentDataObjectFactory
     */
    private $paymentDataObjectFactory;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param BuildCommand $command
     * @param PaymentDataObjectFactory $paymentDataObjectFactory
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        BuildCommand $command,
        PaymentDataObjectFactory $paymentDataObjectFactory
    ) {
        $this->orderRepository = $orderRepository;
        $this->command = $command;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
    }

    /**
     * Place transaction
     *
     * @param int $orderId
     * @return array
     */
    public function placeTransaction($orderId)
    {
        $order = $this->orderRepository->get((int)$orderId);

        $result = $this->command->execute(
            [
                'payment' => $this->paymentDataObjectFactory->create($order->getPayment())
            ]
        )
            ->get();
        return [
            'action' => $result['action'],
            'fields' => array_keys($result['fields']),
            'values' => array_values($result['fields'])
        ];
    }
}
