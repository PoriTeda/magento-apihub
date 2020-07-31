<?php

namespace Riki\CsvOrderMultiple\Model\ImportHandler\Validator;

use Riki\AdvancedInventory\Model\Assignation;
use Riki\CsvOrderMultiple\Model\ImportHandler\RowValidatorInterface;

class WarehouseCode extends AbstractImportValidator
{
    /**
     * @var \Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\CollectionFactory
     */
    protected $pointOfSaleCollectionFactory;

    /**
     * @var \Riki\ShipLeadTime\Helper\Data
     */
    protected $shipLeadTimeDataHelper;

    /**
     * @var \Riki\DeliveryType\Helper\Data
     */
    protected $deliveryDataHelper;

    /**
     * @var int
     */
    protected $placeId;

    /**
     * @var \Riki\AdvancedInventory\Model\Assignation
     */
    protected $assignation;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * WarehouseCode constructor.
     * @param \Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\CollectionFactory $pointOfSaleCollectionFactory
     * @param \Riki\ShipLeadTime\Helper\Data $shipLeadTimeDataHelper
     * @param \Riki\DeliveryType\Helper\Data $deliveryDataHelper
     * @param Assignation $assignation
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     */
    public function __construct(
        \Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\CollectionFactory $pointOfSaleCollectionFactory,
        \Riki\ShipLeadTime\Helper\Data $shipLeadTimeDataHelper,
        \Riki\DeliveryType\Helper\Data $deliveryDataHelper,
        \Riki\AdvancedInventory\Model\Assignation $assignation,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {
        $this->pointOfSaleCollectionFactory = $pointOfSaleCollectionFactory;
        $this->shipLeadTimeDataHelper = $shipLeadTimeDataHelper;
        $this->deliveryDataHelper = $deliveryDataHelper;
        $this->assignation = $assignation;
        $this->productRepository = $productRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($value)
    {
        $this->_clearMessages();

        $warehouseCode = $value['warehouse_code'];

        if (!empty($warehouseCode)) {
            $warehouseCode = strtoupper($warehouseCode);

            /** @var \Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\Collection $collection */
            $collection = $this->pointOfSaleCollectionFactory->create();

            $collection->addFieldToFilter('store_code', $warehouseCode)
                ->setPageSize(1);

            if (!$collection->getSize()) {
                $this->_addMessages(
                    [
                        sprintf(
                            $this->context->retrieveMessageTemplate(
                                RowValidatorInterface::ERROR_INVALID_WAREHOUSE_CODE
                            ),
                            $warehouseCode
                        )
                    ]
                );

                return false;
            } else {
                foreach ($collection->getItems() as $item) {
                    $this->placeId = $item->getPlaceId();
                }
            }

            if ($this->placeId != '') {
                if ($this->validator->getIsListProductValid()) {
                    if (!$this->isActivePlaceDeliveryTypeRegion($this->placeId, $value)) {
                        $this->_addMessages(
                            [
                                sprintf(
                                    $this->context->retrieveMessageTemplate(
                                        RowValidatorInterface::ERROR_WAREHOUSE_DELIVERY_TYPE
                                    ),
                                    $warehouseCode
                                )
                            ]
                        );

                        return false;
                    }

                    if (!$this->checkAvailability($this->placeId)) {
                        $this->_addMessages(
                            [
                                sprintf(
                                    $this->context->retrieveMessageTemplate(
                                        RowValidatorInterface::ERROR_WAREHOUSE_STOCK_STATUS
                                    ),
                                    $warehouseCode
                                )
                            ]
                        );

                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Check Warehouse/delivery type/prefecture is activate or existed if warehouse_code is not empty
     *
     * @param $placeId
     * @param $dataImport
     * @return bool|mixed
     */
    public function isActivePlaceDeliveryTypeRegion($placeId, $dataImport)
    {
        $productDeliveryType = $this->validator->getDeliveryTypeListProductImport();
        $regionCode = $this->context->getRegionCodeByName($dataImport['ship_region']);
        if ($regionCode != null && is_array($productDeliveryType) && !empty($productDeliveryType)) {
            $deliveryType = $this->deliveryDataHelper->getDeliveryTypeOfCoolNormalDmGroup($productDeliveryType);
            return $this->shipLeadTimeDataHelper->isActivePlaceDeliveryTypeRegion(
                $placeId,
                $deliveryType,
                $regionCode
            );
        }

        return false;
    }

    /**
     * Check stock quality for order from this warehouse if warehouse_code is not empty
     *
     * @param $placeId
     * @return bool
     */
    public function checkAvailability($placeId)
    {
        $products = $this->validator->getListProductAvailability();
        if (is_array($products) && !empty($products)) {
            foreach ($products as $product) {
                $neededCheckProductIds = $this->prepareQtyValidationData($product['product_id'], $product['qty']);

                foreach ($neededCheckProductIds as $productId => $qty) {
                    $available = $this->assignation->checkAvailability($productId, $placeId, $qty, null);
                    if ($available['status'] < Assignation::STOCK_STATUS_AVAILABLE_BACK_ORDER) {
                        return false;
                    }
                }
            }
            return true;
        }
        return false;
    }

    /**
     * @param $productId
     * @param $qty
     * @return array
     */
    private function prepareQtyValidationData($productId, $qty)
    {
        try {
            $productModel = $this->productRepository->getById($productId);
        } catch (\Exception $e) {
            return [];
        }

        $neededCheckProductIds = [];

        if ($productModel->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
            $extensionAttr = $productModel->getExtensionAttributes();
            $bundleOptions = $extensionAttr->getBundleProductOptions();

            /** @var \Magento\Bundle\Model\Option $bundleOption */
            foreach ($bundleOptions as $bundleOption) {
                /** @var \Magento\Bundle\Model\Link $productLink */
                foreach ($bundleOption->getProductLinks() as $productLink) {
                    $productIdLink = $productLink->getEntityId();
                    if (!isset($neededCheckProductIds[$productIdLink])) {
                        $neededCheckProductIds[$productIdLink] = 0;
                    }
                    $neededCheckProductIds[$productIdLink] += $productLink->getQty() * $qty;
                }
            }
        } else {
            if (!isset($neededCheckProductIds[$productId])) {
                $neededCheckProductIds[$productId] = 0;
            }
            $neededCheckProductIds[$productId] += $qty;
        }

        return $neededCheckProductIds;
    }
}
