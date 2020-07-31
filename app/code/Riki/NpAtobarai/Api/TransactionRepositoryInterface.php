<?php
namespace Riki\NpAtobarai\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Riki\NpAtobarai\Api\Data\TransactionInterface;
use Riki\NpAtobarai\Api\Data\TransactionSearchResultsInterface;

interface TransactionRepositoryInterface
{
    /**
     * Save transaction
     * @param TransactionInterface $transaction
     * @return TransactionInterface
     * @throws LocalizedException
     */
    public function save(
        TransactionInterface $transaction
    );

    /**
     * Retrieve transaction
     * @param string $transactionId
     * @param bool $forceReload
     * @return TransactionInterface
     * @throws LocalizedException
     */
    public function getById(string $transactionId, $forceReload = false);

    /**
     * Retrieve transaction by shipment id
     * @param string $shipmentId
     * @return TransactionInterface
     * @throws NoSuchEntityException
     */
    public function getByShipmentId($shipmentId);

    /**
     * Retrieve transaction matching the specified criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     *
     * @return TransactionSearchResultsInterface
     * @throws LocalizedException
     */
    public function getList(
        SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete transaction
     * @param TransactionInterface $transaction
     * @return bool true on success
     * @throws LocalizedException
     */
    public function delete(
        TransactionInterface $transaction
    );

    /**
     * Delete transaction by ID
     * @param string $transactionId
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById($transactionId);

    /**
     * Get List Transaction by Order Id
     * @param string $orderId
     * @return TransactionSearchResultsInterface
     * @throws LocalizedException
     */
    public function getListByOrderId($orderId);
}
