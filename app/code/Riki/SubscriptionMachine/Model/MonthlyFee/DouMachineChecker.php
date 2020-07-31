<?php

namespace Riki\SubscriptionMachine\Model\MonthlyFee;

class DouMachineChecker
{
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface
     */
    protected $outOfStockRepository;

    /**
     * DouMachineChecker constructor.
     *
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface $outOfStockRepository
     */
    public function __construct(
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface $outOfStockRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->outOfStockRepository = $outOfStockRepository;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return boolean
     */
    public function isOrderHasFreeDuoMachine($order)
    {
        foreach ($order->getItems() as $orderItem) {
            $additionalData = json_decode($orderItem->getData('additional_data') ?: '{}', true);
            if (isset($additionalData['is_duo_machine']) && $additionalData['is_duo_machine']) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return boolean
     */
    public function isOrderHasOosItemDuoMachine($order)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('original_order_id', $order->getId())
            ->create();
        $outOfStocks = $this->outOfStockRepository->getList($searchCriteria);
        if (!$outOfStocks->getTotalCount()) {
            return false;
        }

        foreach ($outOfStocks->getItems() as $outOfStock) {
            $quoteItemData = json_decode($outOfStock->getData('quote_item_data') ?: '{}', true);
            if (is_array($quoteItemData)) {
                foreach ($quoteItemData as $itemData) {
                    if (isset($itemData['additional_data']) && $itemData['additional_data']) {
                        $additionalData = json_decode($itemData['additional_data'] ?: '{}', true);
                        if (isset($additionalData['is_duo_machine']) && $additionalData['is_duo_machine']) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }
}
