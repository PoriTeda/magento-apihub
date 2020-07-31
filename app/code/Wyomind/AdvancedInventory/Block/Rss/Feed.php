<?php

/**
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Block\Rss;

class Feed extends \Magento\Backend\Block\AbstractBlock implements \Magento\Framework\App\Rss\DataProviderInterface
{

    const CACHE_TAG = 'block_html_rss_advancedinventory_lowstock';

    protected $_rssModel;
    protected $_rssUrlBuilder;
    protected $_coreHelper;
    protected $_posFactory;
    protected $_productFactory;
    protected $_helperPermissions;

    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Catalog\Model\Rss\Product\NotifyStock $rssModel,
        \Magento\Framework\App\Rss\UrlBuilderInterface $rssUrlBuilder,
        \Wyomind\Core\Helper\Data $coreHelper,
        \Wyomind\PointOfSale\Model\PointOfSaleFactory $posFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productFactory,
        \Wyomind\AdvancedInventory\Helper\Permissions $helperPermissions,
        array $data = []
    ) {
        $this->_rssModel = $rssModel;
        $this->_rssUrlBuilder = $rssUrlBuilder;
        $this->_coreHelper = $coreHelper;
        $this->_posFactory = $posFactory;
        $this->_productFactory = $productFactory;
        $this->_helperPermissions = $helperPermissions;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        $this->pos = $this->_posFactory->create()->load($this->getRequest()->getParam('wh'));
        $this->setCacheTags([self::CACHE_TAG]);
        $this->setCacheKey('advancedinventory_rss_stock_' . $this->pos->getData('place_id'));
    }

    /**
     * {@inheritdoc}
     */
    public function getRssData()
    {

        $newUrl = $this->_rssUrlBuilder->getUrl(['_secure' => true, '_nosecret' => false, 'type' => 'ai_rss_feed']);
        $title = __('Low Stock Products for Point of sale / Warehouse ') . $this->pos->getData("name");
        $data = ['title' => $title, 'description' => $title, 'link' => $newUrl, 'charset' => 'UTF-8'];


        foreach ($this->getItems() as $item) {
            $url = $this->getUrl(
                'catalog/product/edit',
                ['id' => $item->getId(), '_secure' => true, '_nosecret' => true]
            );
            $qty = $item->getQty();
            $description = __('%1 has reached a quantity of %2.', $item->getName(), $qty);
            $data['entries'][] = ['title' => $item->getName(), 'link' => $url, 'description' => $description];
        }
        
        return $data;
    }

    public function getItems()
    {
        $globalNotifyStockQty = $this->_coreHelper->getStoreConfig(\Magento\CatalogInventory\Model\Configuration::XML_PATH_NOTIFY_STOCK_QTY);

        $collection = $this->_productFactory->create();
        $collection->addAttributeToSelect('name', true)
                ->joinTable(
                    'advancedinventory_stock',
                    'product_id=entity_id',
                    [
                    'qty' => 'quantity_in_stock'
                    ],
                    "place_id='" . $this->pos->getData("place_id") . "' AND manage_stock=1 AND quantity_in_stock<$globalNotifyStockQty",
                    'inner'
                )
                ->setOrder('qty')
                ->addAttributeToFilter('status', ['in' => 1]);

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheLifetime()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function isAllowed()
    {
        return $this->_helperPermissions->isAllowed($this->pos->getData('place_id'));
    }

    /**
     * {@inheritdoc}
     */
    public function getFeeds()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthRequired()
    {
        return true;
    }
}
