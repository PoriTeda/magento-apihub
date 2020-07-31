<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Gateway\Command;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Command\ResultInterface;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Order;

/**
 * Makes capture or sale transaction.
 */
class CaptureStrategyCommand implements CommandInterface
{
    /**
     * Secure Acceptance sale command name
     */
    const SECURE_ACCEPTANCE_SALE = 'secure_acceptance_sale';

    /**
     * Simple order capture command name
     */
    const SIMPLE_ORDER_CAPTURE = 'simple_order_capture';

    /**
     * Simple order subscription command name
     */
    const SIMPLE_ORDER_SUBSCRIPTION = 'simple_order_subscription';

    /**
     * Simple order sale command name
     * @deprecated
     */
    const SIMPLE_ORDER_SALE = 'simple_order_sale';

    /**
     * @var CommandPoolInterface
     */
    private $commandPool;

    /**
     * @param CommandPoolInterface $commandPool
     */
    public function __construct(CommandPoolInterface $commandPool)
    {
        $this->commandPool = $commandPool;
    }

    /**
     * Executes capture command.
     *
     * If authorization transaction is present then capture performs using Simple Order API.
     * The sale transaction is performed using Secure Acceptance Silent Order.
     *
     * @param array $commandSubject
     * @return ResultInterface|null
     * @throws LocalizedException
     */
    public function execute(array $commandSubject)
    {
        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = SubjectReader::readPayment($commandSubject);

        /** @var Order\Payment $paymentInfo */
        $paymentInfo = $paymentDO->getPayment();
        if ($paymentInfo instanceof Order\Payment
            && $paymentInfo->getAuthorizationTransaction()
        ) {
            return $this->commandPool
                ->get(self::SIMPLE_ORDER_CAPTURE)
                ->execute($commandSubject);
        }

        return $this->commandPool->get(self::SECURE_ACCEPTANCE_SALE)
            ->execute($commandSubject);
    }
}
