<?php

namespace Bluecom\Paygent\Helper;

class HistoryHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Bluecom\Paygent\Model\ResourceModel\PaygentHistory\CollectionFactory
     */
    protected $historyCollectionFactory;

    /**
     * HistoryHelper constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Bluecom\Paygent\Model\ResourceModel\PaygentHistory\CollectionFactory $collectionFactory
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Bluecom\Paygent\Model\ResourceModel\PaygentHistory\CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
        $this->historyCollectionFactory = $collectionFactory;
    }

    /**
     * Get payment agent by order increment id
     *
     * @param $orderIncrementId
     * @return bool|mixed
     */
    public function getPaymentAgentByOrderIncrementId($orderIncrementId)
    {
        /** @var \Bluecom\Paygent\Model\ResourceModel\PaygentHistory\Collection $collection */
        $collection = $this->historyCollectionFactory->create();

        $collection->addFieldToFilter(
            'order_number', $orderIncrementId
        )->addFieldToFilter(
            'type', 'authorize'
        )->setOrder(
            'type', 'DESC'
        )->setOrder(
            'id', 'DESC'
        );

        if ($collection->getSize()) {
            return $collection->setPageSize(1)->getFirstItem()->getData('payment_agent');
        }

        return false;
    }

}
