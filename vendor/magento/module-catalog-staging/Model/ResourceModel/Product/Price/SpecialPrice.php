<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogStaging\Model\ResourceModel\Product\Price;

/**
 * Special price persistence.
 */
class SpecialPrice implements \Magento\Catalog\Api\SpecialPriceInterface
{
    /**
     * Price storage table.
     *
     * @var string
     */
    private $priceTable = 'catalog_product_entity_decimal';

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Attribute
     */
    private $attributeResource;

    /**
     * @var \Magento\Catalog\Api\ProductAttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * Metadata pool.
     *
     * @var \Magento\Framework\EntityManager\MetadataPool
     */
    private $metadataPool;

    /**
     * @var \Magento\Catalog\Model\Product\Price\Validation\Result
     */
    private $validationResult;

    /**
     * @var \Magento\CatalogStaging\Model\Product\UpdateScheduler
     */
    private $updateScheduler;

    /**
     * Special Price attribute ID.
     *
     * @var int
     */
    private $priceAttributeId;

    /**
     * Items per operation.
     *
     * @var int
     */
    private $itemsPerOperation = 500;

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Attribute $attributeResource
     * @param \Magento\Catalog\Api\ProductAttributeRepositoryInterface $attributeRepository
     * @param \Magento\Framework\EntityManager\MetadataPool $metadataPool
     * @param \Magento\Catalog\Model\Product\Price\Validation\Result $validationResult
     * @param \Magento\CatalogStaging\Model\Product\UpdateScheduler $updateScheduler
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Attribute $attributeResource,
        \Magento\Catalog\Api\ProductAttributeRepositoryInterface $attributeRepository,
        \Magento\Framework\EntityManager\MetadataPool $metadataPool,
        \Magento\Catalog\Model\Product\Price\Validation\Result $validationResult,
        \Magento\CatalogStaging\Model\Product\UpdateScheduler $updateScheduler
    ) {
        $this->attributeResource = $attributeResource;
        $this->attributeRepository = $attributeRepository;
        $this->metadataPool = $metadataPool;
        $this->validationResult = $validationResult;
        $this->updateScheduler = $updateScheduler;
    }

    /**
     * {@inheritdoc}
     */
    public function get(array $skus)
    {
        $products = $this->getProductsWithDisabledPreview($skus);
        $selectionIds = [];
        foreach ($products as $id) {
            $selectionIds[] = $id[$this->getEntityLinkField()];
        }
        $priceTable = $this->attributeResource->getTable($this->priceTable);
        $select = $this->attributeResource->getConnection()
            ->select()
            ->from($priceTable)
            ->where($priceTable . '.' . $this->getEntityLinkField() . ' IN (?)', $selectionIds)
            ->where($priceTable . '.attribute_id = ?', $this->getPriceAttributeId());
        $items = $this->attributeResource->getConnection()->fetchAll($select);
        $populatedItems = [];
        foreach ($items as $item) {
            foreach ($products as $product) {
                if ($product[$this->getEntityLinkField()] === $item[$this->getEntityLinkField()]
                    && isset($item['value'])
                ) {
                    $populatedItems[] = [
                        $this->getEntityLinkField() => $item[$this->getEntityLinkField()],
                        'value' => $item['value'],
                        'store_id' => $item['store_id'],
                        'sku' => $product['sku'],
                        'price_from' => date('Y-m-d H:i:s', $product['created_in']),
                        'price_to' => date('Y-m-d H:i:s', $product['updated_in'])
                    ];
                }
            }
        }

        return $populatedItems;
    }

    /**
     * {@inheritdoc}
     */
    public function update(array $prices)
    {
        foreach ($this->validationResult->getFailedRowIds() as $failedRowId) {
            unset($prices[$failedRowId]);
        }
        $newPrices = $this->retrieveNewPrices($prices);
        $this->createProductUpdates($newPrices);
        $connection = $this->attributeResource->getConnection();
        $connection->beginTransaction();

        try {
            $formattedPrices = [];
            /** @var \Magento\Catalog\Api\Data\SpecialPriceInterface $price */
            foreach ($prices as $price) {
                $productPreviews = $this->getProductsWithDisabledPreview([$price->getSku()]);

                foreach ($productPreviews as $productPreview) {
                    if (date('Y-m-d H:i', strtotime($price->getPriceFrom()))
                            == date('Y-m-d H:i', $productPreview['created_in'])
                        && date('Y-m-d H:i', strtotime($price->getPriceTo()))
                            == date('Y-m-d H:i', $productPreview['updated_in'])
                    ) {
                        $formattedPrices[] = [
                            'attribute_id' => $this->getPriceAttributeId(),
                            'store_id' => $price->getStoreId(),
                            $this->getEntityLinkField() => $productPreview[$this->getEntityLinkField()],
                            'value' => $price->getPrice(),
                        ];
                    }
                }
            }
            $this->updateItems($formattedPrices, $this->priceTable);
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw new \Magento\Framework\Exception\CouldNotSaveException(
                __('Could not save Prices.'),
                $e
            );
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(array $prices)
    {
        $skus = array_unique(
            array_map(function ($price) {
                return $price->getSku();
            }, $prices)
        );
        $existingPrices = $this->get($skus);
        $idsToDelete = [];
        foreach ($prices as $key => $price) {
            if (!$price->getPriceFrom()) {
                $this->validationResult->addFailedItem(
                    $key,
                    __(
                        'Invalid attribute %fieldName = %fieldValue.',
                        ['fieldName' => '%fieldName', 'fieldValue' => '%fieldValue']
                    ),
                    ['fieldName' => 'Price From', 'fieldValue' => $price->getPriceFrom()]
                );
                break;
            }
            $priceExists = false;
            foreach ($existingPrices as $existingPrice) {
                if ($this->priceSelectionsAreEqual($price, $existingPrice)
                    && $price->getPrice() == $existingPrice['value']
                ) {
                    $idsToDelete[] = $existingPrice[$this->getEntityLinkField()];
                    $priceExists = true;
                    break;
                }
            }
            if (!$priceExists) {
                $this->validationResult->addFailedItem(
                    $key,
                    __('The requested price is not found.'),
                    [
                        'price' => $price->getPrice(),
                        'sku' => $price->getSku(),
                        'store_id' => $price->getStoreId(),
                        'price_from' => $price->getPriceFrom(),
                        'price_to' => $price->getPriceTo()
                    ]
                );
            }
        }

        $connection = $this->attributeResource->getConnection();
        $connection->beginTransaction();
        try {
            foreach (array_chunk($idsToDelete, $this->itemsPerOperation) as $idsBunch) {
                $this->attributeResource->getConnection()->delete(
                    $this->attributeResource->getTable($this->priceTable),
                    [
                        'attribute_id = ?' => $this->getPriceAttributeId(),
                        $this->getEntityLinkField() . ' IN (?)' => $idsBunch
                    ]
                );
            }
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw new \Magento\Framework\Exception\CouldNotDeleteException(
                __('Could not delete Prices'),
                $e
            );
        }

        return true;
    }

    /**
     * Get link field.
     *
     * @return string
     */
    public function getEntityLinkField()
    {
        return $this->metadataPool->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class)
            ->getLinkField();
    }

    /**
     * Create new product updates.
     *
     * @param array $newPrices
     * @return void
     */
    private function createProductUpdates(array $newPrices)
    {
        foreach ($newPrices as $key => $newPrice) {
            $name = __(
                'Update %1 from %2 to %3.',
                $newPrice->getSku(),
                $newPrice->getPriceFrom(),
                $newPrice->getPriceTo()
            );
            $stagingData = [
                'name' => $name,
                'start_time' => $newPrice->getPriceFrom(),
                'end_time' => $newPrice->getPriceTo()
            ];
            try {
                $this->updateScheduler->schedule($newPrice->getSku(), $stagingData, $newPrice->getStoreId());
            } catch (\Exception $e) {
                $this->validationResult->addFailedItem($key, $e->getMessage());
            }
        }
    }

    /**
     * Update items in database.
     *
     * @param array $items
     * @param string $table
     * @return void
     */
    private function updateItems(array $items, $table)
    {
        foreach (array_chunk($items, $this->itemsPerOperation) as $itemsBunch) {
            $this->attributeResource->getConnection()->insertOnDuplicate(
                $this->attributeResource->getTable($table),
                $itemsBunch,
                ['value']
            );
        }
    }

    /**
     * Get attribute ID.
     *
     * @return int
     */
    private function getPriceAttributeId()
    {
        if (!$this->priceAttributeId) {
            $this->priceAttributeId = $this->attributeRepository->get('special_price')->getAttributeId();
        }

        return $this->priceAttributeId;
    }

    /**
     * Get products with disabled staging preview.
     *
     * @param array $skus
     * @return array
     */
    public function getProductsWithDisabledPreview(array $skus)
    {
        return $this->attributeResource->getConnection()->fetchAll(
            $this->attributeResource->getConnection()
                ->select()
                ->from(
                    $this->attributeResource->getTable('catalog_product_entity'),
                    [$this->getEntityLinkField(), 'sku', 'created_in', 'updated_in', 'entity_id']
                )
                ->where('sku IN (?)', $skus)
                ->where('created_in')
                ->setPart('disable_staging_preview', true)
        );
    }

    /**
     * Retrieve not existing prices.
     *
     * @param array $prices
     * @return array
     */
    private function retrieveNewPrices(array $prices)
    {
        $result = [];
        $skus = array_unique(
            array_map(function ($newPrice) {
                return $newPrice->getSku();
            }, $prices)
        );
        $existingPrices = $this->get($skus);

        foreach ($prices as $key => $price) {
            $priceExists = false;
            foreach ($existingPrices as $existingPrice) {
                if ($this->priceSelectionsAreEqual($price, $existingPrice)) {
                    $priceExists = true;
                    break;
                }
            }

            if (!$priceExists) {
                $result[$key] = $price;
            }
        }

        return $result;
    }

    /**
     * Check that prices are equal.
     *
     * @param \Magento\Catalog\Api\Data\SpecialPriceInterface $price
     * @param array $existingPrice
     * @return bool
     */
    private function priceSelectionsAreEqual(
        \Magento\Catalog\Api\Data\SpecialPriceInterface $price,
        array $existingPrice
    ) {
        return $price->getSku() == $existingPrice['sku']
            && $price->getStoreId() == $existingPrice['store_id']
            && $price->getPriceFrom() == $existingPrice['price_from']
            && $price->getPriceTo() == $existingPrice['price_to'];
    }
}
