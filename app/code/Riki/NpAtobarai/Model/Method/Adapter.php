<?php

namespace Riki\NpAtobarai\Model\Method;

use \Magento\Payment\Gateway\Command\CommandPoolInterface;

/**
 * Class Adapter
 * @package Riki\NpAtobarai\Model\Method
 */
class Adapter
{
    const COMMAND_CODE_REGISTER_ORDER = 'register';
    const COMMAND_CODE_GET_VALIDATION_RESULT = 'validate';
    const COMMAND_CODE_REGISTER_SHIP_OUT = 'register_shipped_out';
    const COMMAND_CODE_GET_PAYMENT_STATUS = 'get_payment_status';
    const COMMAND_CODE_CANCEL = 'cancel';

    /**
     * @var CommandPoolInterface
     */
    private $commandPool;

    /**
     * Adapter constructor.
     * @param CommandPoolInterface|null $commandPool
     */
    public function __construct(
        CommandPoolInterface $commandPool = null
    ) {
        $this->commandPool = $commandPool;
    }

    /**
     * @param \Riki\NpAtobarai\Api\Data\TransactionInterface[] $transactionList
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function register(array $transactionList)
    {
        $this->executeCommand(self::COMMAND_CODE_REGISTER_ORDER, $transactionList);
    }

    /**
     * @param \Riki\NpAtobarai\Api\Data\TransactionInterface[] $transactionList
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function getValidationResult(array $transactionList)
    {
        $this->executeCommand(self::COMMAND_CODE_GET_VALIDATION_RESULT, $transactionList);
    }

    /**
     * @param \Riki\NpAtobarai\Api\Data\TransactionInterface[] $transactionList
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function registerShipmentOut(array $transactionList)
    {
        $this->executeCommand(self::COMMAND_CODE_REGISTER_SHIP_OUT, $transactionList);
    }

    /**
     * @param \Riki\NpAtobarai\Api\Data\TransactionInterface[] $transactionList
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function getPaymentStatus(array $transactionList)
    {
        $npTransactions = [];
        foreach ($transactionList as $npTransaction) {
            $npTransactions[$npTransaction->getNpTransactionId()] = $npTransaction;
        }
        $this->executeCommand(self::COMMAND_CODE_GET_PAYMENT_STATUS, $npTransactions);
    }

    /**
     * @param \Riki\NpAtobarai\Api\Data\TransactionInterface[] $transactionList
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function cancel(array $transactionList)
    {
        $this->executeCommand(self::COMMAND_CODE_CANCEL, $transactionList);
    }

    /**
     * @param string $commandCode
     * @param \Riki\NpAtobarai\Api\Data\TransactionInterface[] $transactionList
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function executeCommand($commandCode, array $transactionList)
    {
        $command = $this->commandPool->get($commandCode);
        $command->execute($transactionList);
    }
}
