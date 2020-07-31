<?php

namespace Riki\NpAtobarai\Api;

interface TransactionManagementInterface
{
    /**
     * @param int $orderId
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException|\Magento\Framework\Exception\NoSuchEntityException|\Magento\Framework\Exception\NotFoundException
     */
    public function createTransactions($orderId);

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return mixed
     */
    public function getOrderTransactions(\Magento\Sales\Model\Order $order);
}
