<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Gateway\Command\Shared;

use Magento\Sales\Model\Order;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order\Payment;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Command\ResultInterface;
use Magento\Payment\Gateway\Command\Result\BoolResultFactory;

/**
 * Class CancelOrderCommand
 */
class CancelOrderCommand implements CommandInterface
{
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;

    /**
     * @var BoolResultFactory
     */
    private $resultFactory;

    /**
     * Constructor
     *
     * @param OrderManagementInterface $orderManagement
     * @param Session $checkoutSession
     * @param BoolResultFactory $boolResultFactory
     */
    public function __construct(
        OrderManagementInterface $orderManagement,
        Session $checkoutSession,
        BoolResultFactory $resultFactory
    ) {
        $this->orderManagement = $orderManagement;
        $this->checkoutSession = $checkoutSession;
        $this->resultFactory = $resultFactory;
    }

    /**
     * Executes command basing on business object
     *
     * @param array $commandSubject
     * @return ResultInterface
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    public function execute(array $commandSubject)
    {
        $paymentDO = SubjectReader::readPayment($commandSubject);

        $this->orderManagement->cancel($paymentDO->getOrder()->getId());

        return $this->resultFactory->create(['result' => $this->checkoutSession->restoreQuote()]);
    }
}
