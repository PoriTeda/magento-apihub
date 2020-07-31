<?php

namespace Riki\PointOfSale\Block\Adminhtml\Manage\Edit\Tab\LeadTime;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /** @var \Riki\ShipLeadTime\Model\LeadtimeFactory  */
    protected $leadTimeFactory;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Riki\ShipLeadTime\Model\LeadtimeFactory $leadTime
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Framework\Registry $coreRegistry,
        \Riki\ShipLeadTime\Model\LeadtimeFactory $leadTime,
        array $data = []
    ) {
        $this->leadTimeFactory = $leadTime;
        $this->coreRegistry = $coreRegistry;
        parent::__construct(
            $context,
            $backendHelper,
            $data
        );
    }

    /**
     *
     */
    protected function _construct()
    {
        parent::_construct();

        $this->setId('leadTimeGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    /**
     *
     */
    public function getWarehouse()
    {
        return $this->coreRegistry->registry('pointofsale');
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $wareHouseId = $this->getWarehouse()->getStoreCode();

        $collection = $this->leadTimeFactory->create()->getCollection();
        $collection->addFieldToFilter('warehouse_id', $wareHouseId);
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'id',
            [
                'header' => __('ID'),
                'type' => 'number',
                'index' => 'id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
            ]
        );

        $this->addColumn(
            'id',
            [
                'header' => __('ID'),
                'type' => 'number',
                'index' => 'id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
            ]
        );

        $this->addColumn(
            'delivery_type_code',
            [
                'header' => __('Shipping method/Delivery type'),
                'index' => 'delivery_type_code',
                'type' => 'options',
                'options' => $this->leadTimeFactory->create()->getDeliveryType()
            ]
        );

        $this->addColumn(
            'pref_id',
            [
                'header' => __('Prefecture'),
                'index' => 'pref_id',
                'type' => 'options',
                'options' => $this->leadTimeFactory->create()->getAllJapanPrefecture()
            ]
        );

        $this->addColumn(
            'shipping_lead_time',
            [
                'header' => __('Lead time (day)'),
                'index' => 'shipping_lead_time',
                'type' => 'text'
            ]
        );
        $this->addColumn(
            'priority',
            [
                'header' => __('Priority/Prefecture'),
                'index' => 'priority',
                'type' => 'text'
            ]
        );
        $this->addColumn(
            'priority',
            [
                'header' => __('Priority/Prefecture'),
                'index' => 'priority',
                'type' => 'number'
            ]
        );

        $this->addColumn(
            'is_active',
            [
                'header' => __('Status'),
                'index' => 'is_active',
                'type' => 'options',
                'options' => ['1' => __('Active'), '0' => __('Inactive')]
            ]
        );

        $this->addColumn(
            'edit',
            [
                'header' => __('Edit'),
                'type' => 'action',
                'getter' => 'getId',
                'actions' => [
                    [
                        'caption' => __('Edit'),
                        'url' => [
                            'base' => 'shipleadtime/index/edit',
                        ],
                        'field' => 'id',
                    ],
                ],
                'filter' => false,
                'sortable' => false,
                'index' => 'stores',
                'header_css_class' => 'col-action',
                'column_css_class' => 'col-action',
            ]
        );
        return parent::_prepareColumns();
    }

    /**
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl(
            'riki_pointofsale/manage/leadTimeGrid',
            ['id' => $this->getWarehouse()->getId(),  '_current' => true]
        );
    }
}
