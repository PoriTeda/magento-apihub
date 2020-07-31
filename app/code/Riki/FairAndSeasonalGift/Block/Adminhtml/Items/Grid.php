<?php
namespace Riki\FairAndSeasonalGift\Block\Adminhtml\Items;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var Registry
     */
    protected $registry;
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
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Bundle\Helper\Data $bundleData
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Riki\FairAndSeasonalGift\Model\ResourceModel\FairDetail\CollectionFactory $fairDetailCollection,
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $data);
        $this->registry = $registry;
        $this->_productFactory = $productFactory;
        $this->_fairDetailCollection = $fairDetailCollection;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('fair_item_search_grid');
        $this->setDefaultSort('id');
        $this->setUseAjax(true);
    }

    /**
     * Apply sorting and filtering to collection
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->_productFactory->create()->getCollection()->setOrder(
            'id'
        )->addAttributeToSelect(
            'name'
        )->addAttributeToSelect(
            'sku'
        )->addAttributeToSelect(
            'price'
        )->addAttributeToSelect(
            'attribute_set_id'
        );

        $currentFairProduct = $this->getCurrentFairProduct();

        if( !empty( $currentFairProduct ) ){
            $collection->addAttributeToFilter('entity_id', ['nin' => $this->getCurrentFairProduct()]);
        }

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Initialize grid columns
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'id',
            [
                'header' => __('ID'),
                'index' => 'entity_id',
                'renderer' => 'Magento\Backend\Block\Widget\Grid\Column\Renderer\Checkbox',
                'type' => 'skip-list',
                'field_name' => 'selected-product'
            ]
        );

        $this->addColumn(
            'name',
            [
                'header' => __('Product'),
                'index' => 'name',
                'header_css_class' => 'col-name',
                'column_css_class' => 'name col-name'
            ]
        );
        $this->addColumn(
            'sku',
            [
                'header' => __('SKU'),
                'width' => '80px',
                'index' => 'sku',
                'header_css_class' => 'col-sku',
                'column_css_class' => 'sku col-sku'
            ]
        );
        $this->addColumn(
            'price',
            [
                'header' => __('Price'),
                'align' => 'center',
                'type' => 'currency',
                'index' => 'price',
                'header_css_class' => 'col-price',
                'column_css_class' => 'col-price'
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * Retrieve grid reload url
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl(
            'fair_seasonal/items/grid',
            ['fair_id' => $this->getCurrentFairId()]
        );
    }

    /**
     * @return Fair object
     */
    public function getCurrentFairId()
    {
        return $this->registry->registry('current_fair_id');
    }

    /**
     * @return array
     */
    public function getCurrentFairProduct(){
        $rs = [];
        $collection = $this->_fairDetailCollection->create();
        $collection->addFieldToFilter('fair_id', $this->getCurrentFairId());
        if( $collection->getSize() ){
            foreach ($collection as $item){
                array_push($rs, $item->getProductId());
            }
        }
        return $rs;
    }

}
