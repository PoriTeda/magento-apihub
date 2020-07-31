<?php
namespace Riki\FairAndSeasonalGift\Block\Adminhtml\Fair\Edit\Tab;

class Items extends \Magento\Framework\View\Element\Template
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
     * Question constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Riki\FairAndSeasonalGift\Model\ResourceModel\FairDetail\CollectionFactory $fairDetailCollection,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_coreRegistry = $registry;
        $this->_productFactory = $productFactory;
        $this->_fairDetailCollection = $fairDetailCollection;
    }

    /**
     * @return Widget
     */
    protected function _prepareLayout()
    {
        $this->setTemplate('Riki_FairAndSeasonalGift::fair/items/form.phtml');

        $this->addChild(
            'add_item_button',
            'Magento\Backend\Block\Widget\Button',
            [
                'id' => 'add_item_button',
                'label' => __('Add Products to Fair'),
                'class' => 'add add-selection'
            ]
        );

        return parent::_prepareLayout();
    }
    
    /**
     * @return string
     */
    public function getAddButtonHtml()
    {
        return $this->getChildHtml('add_item_button');
    }

    /**
     * @return Fair object
     */
    public function getCurrentFair()
    {
        return $this->_coreRegistry->registry('current_fair');
    }

    /**
     * Prepare label for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Related Items');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Related Items');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        if( !$this->getCurrentFair()->getFairId() ) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * get FairProduct
     */
    public function getFairProduct()
    {
        $collection = $this->_fairDetailCollection->create();

        $collection->addFieldToFilter(
            'fair_id', $this->getCurrentFair()->getFairId()
        )->setOrder( 'serial_no', 'ASC');

        if( $collection->getSize() ){
            return $collection;
        } else {
            return false;
        }
    }

    /**
     * get product
     * @param $productId
     * return Product / false
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
     * Retrieve save url
     *
     * @return string
     */
    public function getItemGridUrl()
    {
        return $this->getUrl('fair_seasonal/items/grid', ['fair_id' => $this->getCurrentFair()->getFairId()]);
    }

    /**
     * Retrieve add product url
     *
     * @return string
     */
    public function getAddProductUrl()
    {
        return $this->getUrl('fair_seasonal/items/add', ['fair_id' => $this->getCurrentFair()->getFairId()]);
    }

    /**
     * Retrieve edit item url
     *
     * @return string
     */
    public function getEditItemUrl()
    {
        return $this->getUrl('fair_seasonal/items/save');
    }

    /**
     * Retrieve delete url
     *
     * @return string
     */
    public function getDeleteUrl($item)
    {
        return $this->getUrl('fair_seasonal/items/delete', ['id' => $item->getId(), 'fair_id' => $item->getFairId()]);
    }
    
}