<?php
namespace Riki\FairAndSeasonalGift\Block\Adminhtml\Fair\Edit\Tab;

class Recommend extends \Magento\Framework\View\Element\Template
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * Related Fair Product collection
     *
     * @var \Riki\FairAndSeasonalGift\Model\Fair
     */
    protected $_fairFactory;

    /**
     * @var \Riki\FairAndSeasonalGift\Model\ResourceModel\FairConnection\CollectionFactory
     */
    protected $_relatedFairCollection;

    /**
     * @var \Riki\FairAndSeasonalGift\Model\Options\FairType
     */
    protected $_fairType;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Riki\FairAndSeasonalGift\Model\FairFactory $fairFactory,
        \Riki\FairAndSeasonalGift\Model\ResourceModel\FairConnection\CollectionFactory $relatedFairCollection,
        \Riki\FairAndSeasonalGift\Model\Options\FairType $fairType,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_coreRegistry = $registry;
        $this->_fairFactory = $fairFactory;
        $this->_relatedFairCollection = $relatedFairCollection;
        $this->_fairType = $fairType;
    }

    /**
     * @return Widget
     */
    protected function _prepareLayout()
    {
        $this->setTemplate('Riki_FairAndSeasonalGift::fair/recommend/form.phtml');

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
     * Prepare label for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Recommend products for related fairs');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Recommend products for related fairs');
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

    /**
     * Retrieve get fair item url
     *
     * @return string
     */
    public function getLoadFairItemUrl()
    {
        return $this->getUrl('fair_seasonal/recommend/item');
    }

    /**
     * Retrieve save recommend info url
     *
     * @return string
     */
    public function getSaveRecommendUrl()
    {
        return $this->getUrl('fair_seasonal/recommend/edit');
    }

    /**
     * get Fair Type*
     * @param $fairType
     */
    public function getFairType($fairType)
    {
        return $this->_fairType->getFairTypeValue($fairType);
    }

}