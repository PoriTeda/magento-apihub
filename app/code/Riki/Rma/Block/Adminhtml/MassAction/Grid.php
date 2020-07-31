<?php

namespace Riki\Rma\Block\Adminhtml\MassAction;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Riki\Rma\Model\RequestedMassActionFactory
     */
    protected $massActionFactory;

    protected $massActionOptions;

    protected $rmaCollectionFactory;

    /**
     * Grid constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Riki\Rma\Model\RequestedMassActionFactory $requestedMassActionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Riki\Rma\Model\RequestedMassActionFactory $requestedMassActionFactory,
        \Riki\Rma\Model\Config\Source\Rma\MassAction $massActionOptions,
        \Magento\Rma\Model\ResourceModel\Rma\CollectionFactory $rmaCollectionFactory,
        array $data = []
    ) {
        $this->massActionFactory = $requestedMassActionFactory;
        $this->massActionOptions = $massActionOptions;
        $this->rmaCollectionFactory = $rmaCollectionFactory;

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

        $this->setId('massActionGrid');
        $this->setDefaultSort('requested_at');
        $this->setDefaultDir('DESC');
    }

    /**
     * Prepare related item collection
     *
     * @return \Magento\Rma\Block\Adminhtml\Rma\Grid
     */
    protected function _prepareCollection()
    {
        if (!$this->getCollection()) {
            /** @var \Riki\Rma\Model\RequestedMassAction $reviewModel */
            $massActionModel = $this->massActionFactory->create();
            $collection = $massActionModel->getCollection();
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
            'entity_id',
            [
                'header' => __('ID'),
                'index' => 'entity_id',
                'type' => 'text',
                'header_css_class' => 'col-entity-id',
                'column_css_class' => 'col-entity-id'
            ]
        );

        $this->addColumn(
            'rma_number',
            [
                'header' => __('RMA Number'),
                'type'  =>  'text',
                'index'  =>  'rma_id',
                'renderer' => \Riki\Rma\Block\Adminhtml\MassAction\Grid\Column\Renderer\RmaNumber::class,
                'filter_condition_callback' => [$this, '_filterRmaNumberCondition']
            ]
        );

        $this->addColumn(
            'requested_action',
            [
                'header' => __('Requested Action'),
                'index' => 'action',
                'type' => 'options',
                'options' => $this->massActionOptions->getOptions()
            ]
        );

        $this->addColumn(
            'requested_at',
            [
                'header' => __('Requested At'),
                'index' => 'requested_at',
                'type' => 'datetime',
                'html_decorators' => ['nobr'],
                'header_css_class' => 'col-period',
                'column_css_class' => 'col-period'
            ]
        );

        $this->addColumn(
            'executed_at',
            [
                'header' => __('Executed At'),
                'index' => 'executed_at',
                'type' => 'datetime',
                'html_decorators' => ['nobr'],
                'header_css_class' => 'col-period',
                'column_css_class' => 'col-period'
            ]
        );

        $this->addColumn(
            'requested_by',
            [
                'header' => __('Requested By'),
                'index' => 'requested_by',
                'type' => 'text'
            ]
        );

        $this->addColumn(
            'message',
            [
                'header' => __('Message'),
                'index' => 'message',
                'type' => 'text',
                'filter' => false,
                'sortable' => false
            ]
        );

        /** @var $massActionModel \Riki\Rma\Model\RequestedMassAction */
        $massActionModel = $this->massActionFactory->create();
        $this->addColumn(
            'status',
            [
                'header' => __('Status'),
                'index' => 'status',
                'type' => 'options',
                'options' => $massActionModel->getAllStatus(),
                'header_css_class' => 'col-status',
                'column_css_class' => 'col-status'
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * @param \Riki\Rma\Model\ResourceModel\RequestedMassAction\Collection $collection
     * @param $column
     */
    protected function _filterRmaNumberCondition(\Riki\Rma\Model\ResourceModel\RequestedMassAction\Collection $collection, $column)
    {
        if (!($value = $column->getFilter()->getValue())) {
            return;
        }

        /** @var \Magento\Rma\Model\ResourceModel\Rma\Collection $rmaCollection */
        $rmaCollection = $this->rmaCollectionFactory->create();
        $rmaCollection->addFieldToFilter('increment_id', $value)
            ->addFieldToSelect('entity_id')
            ->setPageSize(1);

        $collection->addFieldToFilter('rma_id', ['in'   =>  $rmaCollection->getSelect()]);
    }
}
