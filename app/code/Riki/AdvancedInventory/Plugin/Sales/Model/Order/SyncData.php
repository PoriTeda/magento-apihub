<?php
namespace Riki\AdvancedInventory\Plugin\Sales\Model\Order;

/**
 * Class SyncData
 * @package Riki\AdvancedInventory\Plugin\Sales\Model\Order
 * @deprecated
 */
class SyncData
{
    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $searchHelper;

    /**
     * @var \Riki\Framework\Helper\Filter
     */
    protected $filter;

    /**
     * @var \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface
     */
    protected $outOfStockRepository;

    /**
     * SyncData constructor.
     *
     * @param \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface $outOfStockRepository
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Riki\Framework\Helper\Filter $filter
     */
    public function __construct(
        \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface $outOfStockRepository,
        \Riki\Framework\Helper\Search $searchHelper,
        \Riki\Framework\Helper\Filter $filter
    ) {
        $this->outOfStockRepository = $outOfStockRepository;
        $this->searchHelper = $searchHelper;
        $this->filter = $filter;
    }

    /**
     * Update data depend on order
     *
     * @param \Magento\Sales\Model\Order $subject
     * @param \Magento\Sales\Model\Order $result
     *
     * @return \Magento\Sales\Model\Order
     */
    public function afterAfterSave(
        \Magento\Sales\Model\Order $subject,
        \Magento\Sales\Model\Order $result
    ) {
        $prefix = 'sync';
        foreach (get_class_methods($this) as $method) {
            if (strpos($method, $prefix) !== 0) {
                continue;
            }

            $field = $this->filter
                ->camelCaseToUnderscore()
                ->toLowercase()
                ->filter(substr($method, strlen($prefix)));
            if (!$result->dataHasChangedFor($field)) {
                continue;
            }

            $this->$method($result);
        }

        return $result;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return \Magento\Sales\Model\Order
     */
    public function syncSubscriptionProfileId(\Magento\Sales\Model\Order $order)
    {
        if (!$order->dataHasChangedFor('subscription_profile_id')) {
            return $order;
        }

        $outOfStocks = $this->searchHelper
            ->getByOriginalOrderId($order->getId())
            ->getAll()
            ->execute($this->outOfStockRepository);
        if (!$outOfStocks) {
            return $order;
        }

        /** @var \Riki\AdvancedInventory\Model\OutOfStock $outOfStock */
        foreach ($outOfStocks as $outOfStock) {
            $outOfStock->setSubscriptionProfileId($order->getData('subscription_profile_id'));
            $this->outOfStockRepository->save($outOfStock);
        }

        return $order;
    }
}