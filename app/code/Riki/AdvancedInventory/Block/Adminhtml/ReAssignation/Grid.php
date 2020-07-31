<?php

namespace Riki\AdvancedInventory\Block\Adminhtml\ReAssignation;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Riki\AdvancedInventory\Model\ReAssignationFactory
     */
    protected $reAssignationFactory;

    /**
     * @var \Riki\AdvancedInventory\Model\Config\Source\ReAssignation\Status
     */
    protected $statusOption;

    /**
     * @var \Riki\AdvancedInventory\Model\Config\Source\ReAssignation\Warehouse
     */
    protected $warehouseOption;

    /**
     * Grid constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Riki\AdvancedInventory\Model\ReAssignationFactory $reAssignationFactory
     * @param \Riki\AdvancedInventory\Model\Config\Source\ReAssignation\Status $statusOption
     * @param \Riki\AdvancedInventory\Model\Config\Source\ReAssignation\Warehouse $warehouseOption
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Riki\AdvancedInventory\Model\ReAssignationFactory $reAssignationFactory,
        \Riki\AdvancedInventory\Model\Config\Source\ReAssignation\Status $statusOption,
        \Riki\AdvancedInventory\Model\Config\Source\ReAssignation\Warehouse $warehouseOption,
        array $data = []
    ) {
    
        $this->reAssignationFactory = $reAssignationFactory;
        $this->statusOption = $statusOption;
        $this->warehouseOption = $warehouseOption;

        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Initialize grid
     *
     * @return void
     */
    public function _construct()
    {
        parent::_construct();

        $this->setId('reassignationGrid');
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        if (!$this->getCollection()) {
            /** @var \Riki\AdvancedInventory\Model\ReAssignation $reviewModel */
            $reAssignationModel = $this->reAssignationFactory->create();
            $collection = $reAssignationModel->getCollection();
            $this->setCollection($collection);
        }
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
                'column_css_class' => 'col-id'
            ]
        );

        $this->addColumn(
            'order_increment_id',
            [
                'header' => __('Order Increment ID'),
                'index' => 'order_increment_id',
                'header_css_class' => 'col-name',
                'column_css_class' => 'col-name'
            ]
        );

        $this->addColumn(
            'warehouse_code',
            [
                'header' => __('To Warehouse'),
                'index' => 'warehouse_code'
            ]
        );

        $this->addColumn(
            'created_at',
            [
                'header' => __('Uploaded Date'),
                'index' => 'created_at',
                'type' => 'datetime'
            ]
        );

        $this->addColumn(
            'uploaded_by',
            [
                'header' => __('Uploaded User'),
                'index' => 'uploaded_by',
                'escape' => true
            ]
        );

        $this->addColumn(
            'updated_at',
            [
                'header' => __('Last Updated Date'),
                'index' => 'updated_at',
                'type' => 'datetime'
            ]
        );

        $this->addColumn(
            'message',
            [
                'header' => __('Error Description'),
                'index' => 'message',
                'sortable' => false,
                'filter' => false,
                'escape' => true
            ]
        );

        $this->addColumn(
            'status',
            [
                'header' => __('Status'),
                'index' => 'status',
                'type' => 'options',
                'options' => $this->statusOption->getOptions(),
                'header_css_class' => 'col-status',
                'column_css_class' => 'col-status'
            ]
        );

        $this->addColumn(
            'action',
            [
                'header' => __('Action'),
                'sortable' => false,
                'filter' => false,
                'renderer' => 'Riki\AdvancedInventory\Block\Adminhtml\ReAssignation\Grid\Renderer\Action',
                'header_css_class' => 'col-action',
                'column_css_class' => 'col-action'
            ]
        );

        return parent::_prepareColumns();
    }
}
