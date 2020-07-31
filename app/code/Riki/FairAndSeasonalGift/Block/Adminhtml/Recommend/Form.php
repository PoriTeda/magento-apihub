<?php
namespace Riki\FairAndSeasonalGift\Block\Adminhtml\Recommend;

class Form extends \Magento\Framework\View\Element\Template
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
     * Related Fair collection
     *
     * @var \Riki\FairAndSeasonalGift\Model\ResourceModel\FairConnection\Collection
     */
    protected $_relatedFairCollection;

    /**
     * Question constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Riki\FairAndSeasonalGift\Model\ResourceModel\FairConnection\CollectionFactory $relatedCollection,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_coreRegistry = $registry;
        $this->_relatedFairCollection = $relatedCollection;
    }

    /**
     * @return Widget
     */
    protected function _prepareLayout()
    {
        $this->setTemplate('Riki_FairAndSeasonalGift::fair/recommend/recommend_form.phtml');

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
     * {@inheritdoc} get related fair of current fair
     */
    public function getRelatedFair()
    {
        if(!$this->getCurrentFair()->getFairId()) {
            return false;
        }
        $collection = $this->_relatedFairCollection->create();
        $collection->join(
            ['riki_fair_management'],
            'main_table.fair_related_id = riki_fair_management.fair_id',
            array()
        )->addFieldToFilter(
            'main_table.fair_id', $this->getCurrentFair()->getFairId()
        )->setOrder(
            'fair_related_order', 'ASC'
        )->getSelect()->columns([
            'riki_fair_management.fair_code',
            'riki_fair_management.fair_year',
            'riki_fair_management.fair_type',
            'riki_fair_management.fair_name',
            'riki_fair_management.start_date',
            'riki_fair_management.end_date'
        ]);

        if( $collection->getSize() ){
            return $collection;
        } else {
            return false;
        }
    }
    
}