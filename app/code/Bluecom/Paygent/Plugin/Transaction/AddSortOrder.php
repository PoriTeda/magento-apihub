<?php
namespace Bluecom\Paygent\Plugin\Transaction;

class AddSortOrder
{
    /**
     * add sort order for transaction collection
     *
     * @param \Magento\Sales\Model\Order\Payment\Transaction\Repository $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\Api\SearchCriteria $searchCriteria
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    public function aroundGetList(
        \Magento\Sales\Model\Order\Payment\Transaction\Repository $subject,
        \Closure $proceed,
        \Magento\Framework\Api\SearchCriteria $searchCriteria
    ) {
        $collection = $proceed($searchCriteria);

        $sortOrders = $searchCriteria->getSortOrders();

        if ($sortOrders === null) {
            $sortOrders = [];
        }

        /** @var \Magento\Framework\Api\SortOrder $sortOrder */
        foreach ($sortOrders as $sortOrder) {
            $field = $sortOrder->getField();
            $collection->addOrder(
                $field,
                ($sortOrder->getDirection() == \Magento\Framework\Api\SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
            );
        }

        return $collection;
    }
}
