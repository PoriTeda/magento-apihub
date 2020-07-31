<?php
namespace Riki\SapIntegration\Plugin\Rma\Model\Item;

class SyncData
{
    /**
     * @var \Magento\Sales\Api\OrderItemRepositoryInterface
     */
    protected $orderItemRepository;
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $searchHelper;

    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    protected $storeEmulation;

    /**
     * SyncData constructor.
     *
     * @param \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository
     * @param \Magento\Store\Model\App\Emulation $storeEmulation
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Riki\Framework\Helper\Search $searchHelper
     */
    public function __construct(
        \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository,
        \Magento\Store\Model\App\Emulation $storeEmulation,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Riki\Framework\Helper\Search $searchHelper
    ) {
        $this->orderItemRepository = $orderItemRepository;
        $this->productRepository = $productRepository;
        $this->searchHelper = $searchHelper;
        $this->storeEmulation = $storeEmulation;
    }

    /**
     * Sync data
     *
     * @param \Magento\Rma\Model\Item $subject
     *
     * @return mixed[]
     */
    public function beforeBeforeSave(\Magento\Rma\Model\Item $subject)
    {
        if ($subject->getId()) {
            return [];
        }
        /*get rma store id*/
        $storeId = (int)$subject->getRma()->getStoreId();
        $this->storeEmulation->startEnvironmentEmulation($storeId);

        $product = $this->searchHelper
            ->getBySku($subject->getData('product_sku'))
            ->getOne()
            ->execute($this->productRepository);
        if ($product instanceof \Magento\Catalog\Model\Product) {
            $subject->setData('gps_price_ec', $product->getData('gps_price_ec'));

            $subject->setData('material_type',
                is_array($product->getAttributeText('material_type'))
                    ? implode(',', $product->getAttributeText('material_type'))
                    : $product->getAttributeText('material_type')
            );

            $subject->setData('sap_interface_excluded', $product->getData('sap_interface_excluded'));
        }

        /*get order item data for this return item*/
        $orderItem = $this->orderItemRepository->get($subject->getData('order_item_id'));

        if ($orderItem) {

            if ($orderItem->getData('sales_organization')) {
                /*sync sales_organization from sales order item with rma item*/
                $subject->setData('sales_organization',$orderItem->getData('sales_organization'));
            }

            if ($orderItem->getData('distribution_channel')) {
                /*sync sales_organization from sales order item with rma item*/
                $subject->setData('distribution_channel',$orderItem->getData('distribution_channel'));
            }
        }

        $this->storeEmulation->stopEnvironmentEmulation();

        return [];
    }
}