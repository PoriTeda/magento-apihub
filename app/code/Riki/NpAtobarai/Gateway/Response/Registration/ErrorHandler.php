<?php

namespace Riki\NpAtobarai\Gateway\Response\Registration;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Psr\Log\LoggerInterface;
use Riki\NpAtobarai\Api\Data\TransactionInterfaceFactory;
use Riki\NpAtobarai\Api\TransactionRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class ErrorHandler implements HandlerInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * TransactionHandler constructor.
     * @param LoggerInterface $logger
     * @param OrderRepositoryInterface $orderRepositoryInterface
     */
    public function __construct(
        LoggerInterface $logger,
        OrderRepositoryInterface $orderRepositoryInterface
    ) {
        $this->logger = $logger;
        $this->orderRepository = $orderRepositoryInterface;
    }

    /**
     * @inheritdoc
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!empty($response['errors'])) {
            foreach ($response['errors'] as $errorItem) {
                try {
                    if ($this->_updateTransactionError($handlingSubject, $errorItem)) {
                        $this->logger->info(
                            __('[RegisterTransaction]Transaction #%1 has been update error code', $errorItem['id']),
                            $errorItem
                        );
                    } else {
                        $this->logger->info(
                            __('[RegisterTransaction]Register Transaction have response received format wrong'),
                            $errorItem
                        );
                    }
                } catch (\Exception $e) {
                    $this->logger->critical($e);
                }
            }
        }
    }

    /**
     * @param mixed $handlingSubject
     * @param array $errorItem
     * @return bool
     */
    protected function _updateTransactionError($handlingSubject, $errorItem)
    {
        if (isset($errorItem['id']) && $errorItem['codes'] && isset($handlingSubject[$errorItem['id']])) {
            $errorCode = implode(',', $errorItem['codes']);
            $transaction = $handlingSubject[$errorItem['id']];
            $transaction->setRegisterErrorCodes($errorCode);
            $transaction->save();
            return true;
        }
        return false;
    }
}
