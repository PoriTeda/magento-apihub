<?php

namespace Riki\SapIntegration\Plugin\Sales\Model\Order\Shipment\Item;

class SyncData
{
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
     * @param \Magento\Store\Model\App\Emulation $storeEmulation
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Riki\Framework\Helper\Search $searchHelper
     */
    public function __construct(
        \Magento\Store\Model\App\Emulation $storeEmulation,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Riki\Framework\Helper\Search $searchHelper
    ) {
        $this->storeEmulation = $storeEmulation;
        $this->productRepository = $productRepository;
        $this->searchHelper = $searchHelper;
    }

    /**
     * Sync data
     *
     * @param \Magento\Sales\Model\Order\Shipment\Item $subject
     *
     * @return mixed[]
     */
    public function beforeBeforeSave(\Magento\Sales\Model\Order\Shipment\Item $subject)
    {
        if ($subject->getId()) {
            return [];
        }

        /*get shipment store id*/
        $storeId = (int)$subject->getShipment()->getStoreId();
        $this->storeEmulation->startEnvironmentEmulation($storeId);

        $product = $this->searchHelper
            ->getByEntityId($subject->getProductId())
            ->getOne()
            ->execute($this->productRepository);

        if ($product instanceof \Magento\Catalog\Model\Product) {

            /*set gps price ec for sales shipment item*/
            $subject->setData('gps_price_ec', $product->getData('gps_price_ec'));

            /*set material type for sales shipment item*/
            $subject->setData('material_type',
                is_array($product->getAttributeText('material_type'))
                    ? implode(',', $product->getAttributeText('material_type'))
                    : $product->getAttributeText('material_type')
            );

            $subject->setData('sap_interface_excluded', $product->getData('sap_interface_excluded'));
        }

        $this->storeEmulation->stopEnvironmentEmulation();
        return [];
    }
}