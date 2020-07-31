<?php
namespace Riki\Rma\Plugin\Rma\Model;

class Item
{
    /**
     * @var \Riki\Rma\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;

    /**
     * Item constructor.
     *
     * @param \Riki\Rma\Helper\Data $dataHelper
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     */
    public function __construct(
        \Riki\Rma\Helper\Data $dataHelper,
        \Magento\Catalog\Model\ProductRepository $productRepository
    ) {
        $this->dataHelper = $dataHelper;
        $this->productRepository = $productRepository;
    }

    /**
     * @param \Magento\Rma\Model\Item $subject
     *
     * @return array
     */
    public function beforeBeforeSave(\Magento\Rma\Model\Item $subject)
    {
        if ($subject->getId()) {
            return [];
        }

        $orderItem = $this->dataHelper->getRmaItemOrderItem($subject);
        if ($orderItem) {
            $subject->setData('free_of_charge', $orderItem->getData('free_of_charge'));
            $subject->setData('booking_wbs', $orderItem->getData('booking_wbs'));
            $subject->setData('foc_wbs', $orderItem->getData('foc_wbs'));
        }

        return [];
    }
}