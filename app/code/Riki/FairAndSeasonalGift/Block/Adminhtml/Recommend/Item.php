<?php
namespace Riki\FairAndSeasonalGift\Block\Adminhtml\Recommend;

class Item extends \Magento\Framework\View\Element\Template
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * Related Fair Product collection
     *
     * @var \Riki\FairAndSeasonalGift\Model\ResourceModel\FairDetail\Collection
     */
    protected $_fairDetailCollection;

    /**
     * @var \Riki\FairAndSeasonalGift\Model\ResourceModel\FairRecommendation\CollectionFactory
     */
    protected $_recommendCollection;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Riki\FairAndSeasonalGift\Model\ResourceModel\FairDetail\CollectionFactory $fairDetailCollection,
        \Riki\FairAndSeasonalGift\Model\ResourceModel\FairRecommendation\CollectionFactory $recommendCollection,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_coreRegistry = $registry;
        $this->_productFactory = $productFactory;
        $this->_fairDetailCollection = $fairDetailCollection;
        $this->_recommendCollection = $recommendCollection;
    }

    /**
     * @return Widget
     */
    protected function _prepareLayout()
    {
        $this->setTemplate('Riki_FairAndSeasonalGift::fair/recommend/item.phtml');

        return parent::_prepareLayout();
    }

    /**
     * @return Fair object
     */
    public function getCurrentFair()
    {
        return $this->_coreRegistry->registry('current_fair');
    }

    /**
     * @return Fair object
     */
    public function getRelatedFairId()
    {
        return $this->_coreRegistry->registry('related_fair_id');
    }

    /**
     * @param $fairId
     * @return bool|\Riki\FairAndSeasonalGift\Model\ResourceModel\FairDetail\Collection
     */
    public function getFairProduct($fairId)
    {
        $collection = $this->_fairDetailCollection->create();

        $collection->addFieldToFilter(
            'fair_id', $fairId
        )->setOrder( 'serial_no', 'ASC');

        if( $collection->getSize() ){
            return $collection;
        } else {
            return false;
        }
    }

    /**
     * @param $productId
     * @return bool|\Magento\Catalog\Model\Product
     */
    public function getProduct($productId)
    {
        $product = $this->_productFactory->create();
        $product->load($productId);
        if($product->getId()){
            return $product;
        } else {
            return false;
        }
    }

    /**
     * @param $fairId
     * @param $recommendedFairId
     * @param $recommendedProductId
     * @return int
     */
    public function getSelectedRecommentItem($fairId, $recommendedFairId, $recommendedProductId)
    {
        $collection = $this->_recommendCollection->create();
        $collection->addFieldToFilter('fair_id', $fairId)
            ->addFieldToFilter('recommended_fair_id', $recommendedFairId)
            ->addFieldToFilter('recommended_product_id', $recommendedProductId);

        if( $collection->getSize() ){
            return $collection->getFirstItem()->getProductId();
        } else {
            return 0;
        }
    }
}