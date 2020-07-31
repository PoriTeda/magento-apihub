<?php
namespace Riki\FairAndSeasonalGift\Block\Adminhtml\Fair\Edit\Tab;

class Related extends \Magento\Framework\View\Element\Template
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * Fair collection
     *
     * @var \Riki\FairAndSeasonalGift\Model\ResourceModel\Fair\Collection
     */
    protected $_fairCollection;

    /**
     * Related Fair collection
     *
     * @var \Riki\FairAndSeasonalGift\Model\ResourceModel\FairConnection\Collection
     */
    protected $_relatedFairCollection;

    /**
     * Fair Type helper
     *
     * @var \Riki\FairAndSeasonalGift\Model\Options\FairType
     */
    protected $_fairType;

    /**
     * Question constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Riki\FairAndSeasonalGift\Model\ResourceModel\Fair\CollectionFactory $faircollection,
        \Riki\FairAndSeasonalGift\Model\ResourceModel\FairConnection\CollectionFactory $relatedFairCollection,
        \Riki\FairAndSeasonalGift\Model\Options\FairType $fairType,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_coreRegistry = $registry;
        $this->_fairCollection = $faircollection;
        $this->_relatedFairCollection = $relatedFairCollection;
        $this->_fairType = $fairType;
    }

    /**
     * @return Widget
     */
    protected function _prepareLayout()
    {
        $this->setTemplate('Riki_FairAndSeasonalGift::fair/related/form.phtml');

        $onclick = "submitAndReloadArea($('fair_seasonal_related_container').parentNode, '" . $this->getSubmitUrl() . "')";

        $this->addChild(
            'add_button',
            'Magento\Backend\Block\Widget\Button',
            [
                'label' => __('Add Related Fair'),
                'class' => 'save',
                'id' => 'add_related_fair',
                'onclick' => $onclick
            ]
        );

        return parent::_prepareLayout();
    }
    
    /**
     * @return string
     */
    public function getAddButtonHtml()
    {
        return $this->getChildHtml('add_button');
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
        return __('Related Fairs');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Related Fairs');
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
     * {@inheritdoc} get list fair exclude current fair
     */
    public function getRelatedFairOption()
    {
        if(!$this->getCurrentFair()->getFairId()) {
            return false;
        } else {
            $collection = $this->_fairCollection->create();
            $collection->addFieldToFilter(
                'fair_id', ['neq' => $this->getCurrentFair()->getFairId()]
            )->addFieldToSelect(['fair_id', 'fair_name']);
            return $collection->getData();
        }
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
     * Retrieve save url
     *
     * @return string
     */
    public function getSubmitUrl()
    {
        return $this->getUrl('fair_seasonal/related/save', ['fair_id' => $this->getCurrentFair()->getFairId()]);
    }

    /**
     * Retrieve delete url
     *
     * @return string
     */
    public function getDeleteUrl($item)
    {
        return $this->getUrl('fair_seasonal/related/delete', ['id' => $item->getId(), 'fair_id' => $item->getFairId()]);
    }

    /**
     * Retrieve delete url
     *
     * @return string
     */
    public function getSaveRelatedUrl()
    {
        return $this->getUrl('fair_seasonal/related/order');
    }

    /**
     * get Fair Type
     * @param $fairType
     */
    public function getFairType($fairType)
    {
        return $this->_fairType->getFairTypeValue($fairType);
    }
    
}