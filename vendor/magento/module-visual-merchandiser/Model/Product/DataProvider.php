<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VisualMerchandiser\Model\Product;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

/**
 * Class DataProvider
 */
class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var string
     */
    protected $positionCacheKey;

    /**
     * @var \Magento\VisualMerchandiser\Model\Position\Cache
     */
    protected $cache;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\VisualMerchandiser\Model\Position\Cache $cache
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\VisualMerchandiser\Model\Position\Cache $cache,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);

        $this->request = $request;
        $this->cache = $cache;

        $this->collection = $collectionFactory->create()->addAttributeToSelect(
            'sku'
        )->addAttributeToSelect(
            'name'
        )->addAttributeToSelect(
            'attribute_set_id'
        )->addAttributeToSelect(
            'type_id'
        )->addAttributeToSelect(
            'price'
        );

        $this->collection->joinField(
            'qty',
            'cataloginventory_stock_item',
            'qty',
            'product_id=entity_id',
            '{{table}}.stock_id=1',
            'left'
        );

        $this->prepareUpdateUrl();
    }

    /**
     * @param \Magento\Framework\Api\Filter $filter
     * @return void
     */
    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        if ($filter->getField() != 'fulltext') {
            $this->collection->addAttributeToFilter(
                $filter->getField(),
                [$filter->getConditionType() => $filter->getValue()]
            );
        } else {
            $value = trim($filter->getValue());
            $this->collection->addAttributeToFilter(
                [
                    ['attribute' => 'name', 'like' => "%{$value}%"],
                    ['attribute' => 'sku', 'like' => "%{$value}%"]
                ]
            );
        }
    }

    /**
     * Prepares update url
     *
     * @return void
     */
    protected function prepareUpdateUrl()
    {
        if (!isset($this->data['config']['filter_url_params'])) {
            return;
        }
        foreach ($this->data['config']['filter_url_params'] as $paramName => $paramValue) {
            if ('*' == $paramValue) {
                $paramValue = $this->request->getParam($paramName);
                $this->positionCacheKey = $paramValue;
            }

            if ($paramValue) {
                $this->data['config']['update_url'] = sprintf(
                    '%s%s/%s',
                    $this->data['config']['update_url'],
                    $paramName,
                    $paramValue
                );
            }
        }
    }

    /**
     * Sets the position values
     *
     * @return void
     */
    public function addPositionData()
    {
        $positions = $this->cache->getPositions($this->positionCacheKey);

        if ($positions === false) {
            return;
        }

        foreach ($this->collection as $item) {
            if (array_key_exists($item->getEntityId(), $positions)) {
                $item->setPosition(
                    $positions[$item->getEntityId()]
                );
                $item->setIds(
                    $item->getEntityId()
                );
            } else {
                $item->setIds(null);
                $item->setPosition(null);
            }
        }
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        $this->addPositionData();
        $arrItems = [];
        $arrItems['totalRecords'] = $this->collection->getSize();
        $arrItems['items'] = [];
        $arrItems['selectedData'] = $this->cache->getPositions($this->positionCacheKey);
        $arrItems['allIds'] = $this->collection->getAllIds();

        foreach ($this->collection->getItems() as $item) {
            $arrItems['items'][] =  $item->toArray();
        }

        return $arrItems;
    }
}
