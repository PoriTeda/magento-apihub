<?php

namespace Riki\Catalog\Block\Search;

class Toolbar extends \Magento\Catalog\Block\Product\ProductList\Toolbar
{
    protected $_collectionSize;
    /**
     * @var \Riki\Sales\Helper\ConnectionHelper
     */
    protected $_connectionHelper;

    /**
     * Toolbar constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Catalog\Model\Session $catalogSession
     * @param \Magento\Catalog\Model\Config $catalogConfig
     * @param \Magento\Catalog\Model\Product\ProductList\Toolbar $toolbarModel
     * @param \Magento\Framework\Url\EncoderInterface $urlEncoder
     * @param \Magento\Catalog\Helper\Product\ProductList $productListHelper
     * @param \Magento\Framework\Data\Helper\PostHelper $postDataHelper
     * @param \Riki\Sales\Helper\ConnectionHelper $connectionHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Model\Session $catalogSession,
        \Magento\Catalog\Model\Config $catalogConfig,
        \Magento\Catalog\Model\Product\ProductList\Toolbar $toolbarModel,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        \Magento\Catalog\Helper\Product\ProductList $productListHelper,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Riki\Sales\Helper\ConnectionHelper $connectionHelper,
        array $data = []
    ) {
        $this->_connectionHelper = $connectionHelper;
        parent::__construct($context, $catalogSession, $catalogConfig, $toolbarModel, $urlEncoder, $productListHelper, $postDataHelper, $data);
    }

    public function setCollection($collection)
    {
        $this->_collection = $collection;

        /*get collection select count sql*/
        $selectCountSql = $collection->getSelectCountSql();

        $this->setCollectionSize($selectCountSql, $collection->getSize());

        $this->_collection->setCurPage($this->getCurrentPage());

        // we need to set pagination only if passed value integer and more that 0
        $limit = (int)$this->getLimit();

        if ($limit) {
            $this->_collection->setPageSize($limit);
        }

        if ($this->getCurrentOrder()) {
            $this->_collection->setOrder($this->getCurrentOrder(), $this->getCurrentDirection());
        }

        return $this;
    }

    /**
     * @param $selectCountSql
     * @param $collectionSize
     */
    public function setCollectionSize($selectCountSql, $collectionSize)
    {
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $connection */
        $connection= $this->_connectionHelper->getDefaultConnection();
        $size = $connection->fetchOne($selectCountSql);
        if ($size) {
            $this->_collectionSize = $size;
        } else {
            $this->_collectionSize = $collectionSize;
        }
    }

    public function getCollectionSize()
    {
        return $this->_collectionSize;
    }

    /**
     * @return int
     */
    public function getTotalNum()
    {
        return $this->getCollectionSize();
    }

    /**
     * @return int
     */
    public function getLastPageNum()
    {
        $collectionSize = (int)$this->_collectionSize;

        if (0 === $collectionSize) {
            return 1;
        } else {
            $limit = (int)$this->getLimit();
            if ($limit) {
                return ceil($collectionSize / $this->getLimit());
            }
        }
        return 1;
    }

    /**
     * Render pagination HTML
     *
     * @return string
     */
    public function getPagerHtml()
    {
        $pagerBlock = $this->getChildBlock('product_list_toolbar_pager');

        if ($pagerBlock instanceof \Magento\Framework\DataObject) {
            /* @var $pagerBlock \Riki\Catalog\Block\Search\Pager */
            $pagerBlock->setAvailableLimit($this->getAvailableLimit());

            $pagerBlock->setUseContainer(
                false
            )->setShowPerPage(
                false
            )->setShowAmounts(
                false
            )->setFrameLength(
                $this->_scopeConfig->getValue(
                    'design/pagination/pagination_frame',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
            )->setJump(
                $this->_scopeConfig->getValue(
                    'design/pagination/pagination_frame_skip',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
            )->setLimit(
                $this->getLimit()
            )->setCollection(
                $this->getCollection()
            )->setCollectionSize(
                $this->getCollectionSize()
            );

            return $pagerBlock->toHtml();
        }

        return '';
    }
}
