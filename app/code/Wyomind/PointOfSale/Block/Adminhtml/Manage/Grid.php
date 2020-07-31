<?php

namespace Wyomind\PointOfSale\Block\Adminhtml\Manage;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{

    public $collectionFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Theme\Model\Layout\Source\Layout $pageLayout
     * @param \Magento\Cms\Model\Page $cmsPage
     * @param \Magento\Cms\Model\ResourceModel\Page\CollectionFactory $collectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\CollectionFactory $collectionFactory,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('manageGrid');
        $this->setDefaultSort('place_id');
        $this->setDefaultDir('ASC');
    }

    /**
     * Prepare collection
     *
     * @return \Magento\Backend\Block\Widget\Grid
     */
    public function _prepareCollection()
    {
        $collection = $this->collectionFactory->create();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare columns
     *
     * @return \Magento\Backend\Block\Widget\Grid\Extended
     */
    protected function _prepareColumns()
    {
        $this->addColumn('place_id', ['width' => '80px', 'type' => 'number', 'header' => __('Id'), 'index' => 'place_id']);
        $this->addColumn(
            'name',
            [
                'header' => __('Store'),
                'index' => "name",
                'renderer' => 'Wyomind\PointOfSale\Block\Adminhtml\Manage\Renderer\Store',
                'sortable' => false
            ]
        );
        $this->addColumn(
            'store_id',
            [
                'header' => __('Store view selection'),
                'renderer' => 'Wyomind\PointOfSale\Block\Adminhtml\Manage\Renderer\Storeview',
                'sortable' => false,
                'filter' => false
            ]
        );
        $this->addColumn(
            'customer_group',
            [
                'header' => __('Customer Group selection'),
                'renderer' => 'Wyomind\PointOfSale\Block\Adminhtml\Manage\Renderer\Customergroup',
                'sortable' => false,
                'filter' => false
            ]
        );
        $this->addColumn(
            'position',
            [
                'header' => __('Order'),
                'index' => 'position',
                'width' => '50',
                'sortable' => true,
                'filter' => false
            ]
        );
        $this->addColumn(
            'status',
            [
                'header' => __('Status'),
                'align' => 'left',
                'width' => '80px',
                'index' => 'status',
                'type' => 'options',
                'options' => [
                    0 => __('Warehouse (hidden)'),
                    1 => __('Point of Sales (visible)'),
                ],
            ]
        );
        $this->addColumn(
            'action',
            [
                'header' => __('Action'),
                'align' => 'left',
                'index' => 'action',
                'filter' => false,
                'sortable' => false,
                'renderer' => 'Wyomind\PointOfSale\Block\Adminhtml\Manage\Renderer\Action',
            ]
        );

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('id');

        $this->getMassactionBlock()->addItem(
            'delete',
            [
                'label' => __('Delete'),
                'url' => $this->getUrl('pointofsale/manage/delete'),
                'confirm' => __('Are you sure?')
            ]
        );

        return $this;
    }

    /**
     * Row click url
     *
     * @param \Magento\Object $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', ['id' => $row->getId()]);
    }
}
