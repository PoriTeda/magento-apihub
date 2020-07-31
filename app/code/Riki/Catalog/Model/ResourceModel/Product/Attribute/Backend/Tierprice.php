<?php
namespace Riki\Catalog\Model\ResourceModel\Product\Attribute\Backend;

class Tierprice extends \Magento\Catalog\Model\ResourceModel\Product\Attribute\Backend\Tierprice
{
    const PRELOAD_PRODUCT_IDS_KEY = 'tier_price_preload_product_ids';

    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * Tierprice constructor.
     *
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param null $connectionName
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    ) {
        $this->registry = $registry;
        $this->functionCache = $functionCache;

        parent::__construct($context, $connectionName);
    }

    /**
     * {@inheritdoc}
     *
     * @param int $productId
     * @param null $websiteId
     *
     * @return array
     */
    public function loadPriceData($productId, $websiteId = null)
    {
        $cacheKey = [$productId, $websiteId];
        if ($this->functionCache->has($cacheKey)) {
            return $this->functionCache->load($cacheKey);
        }

        $productIds = [];
        $productIds[] = $productId;
        $preloadIds = $this->registry->registry(self::PRELOAD_PRODUCT_IDS_KEY);
        if ($preloadIds) {
            array_push($productIds, ...$preloadIds);
            $this->registry->unregister(self::PRELOAD_PRODUCT_IDS_KEY);
        }

        $connection = $this->getConnection();

        $columns = [
            'entity_id' => 'entity_id',
            'price_id' => $this->getIdFieldName(),
            'website_id' => 'website_id',
            'all_groups' => 'all_groups',
            'cust_group' => 'customer_group_id',
            'price' => 'value',
        ];

        $columns = $this->_loadPriceDataColumns($columns);

        $select = $connection->select()->from($this->getMainTable(), $columns)->where('entity_id IN (?)', $productIds);

        $this->_loadPriceDataSelect($select);

        if ($websiteId !== null) {
            if ($websiteId == '0') {
                $select->where('website_id = ?', $websiteId);
            } else {
                $select->where('website_id IN(?)', [0, $websiteId]);
            }
        }

        $tierPrices = [];
        foreach ($connection->fetchAll($select) as $price) {
            $tierPrices[$price['entity_id']][] = $price;
        }

        foreach ($productIds as $id) {
            $cacheKey = [$id, $websiteId];
            $this->functionCache->store(isset($tierPrices[$id]) ? $tierPrices[$id] : [], $cacheKey);
        }

        return isset($tierPrices[$productId]) ? $tierPrices[$productId] : [];
    }
}