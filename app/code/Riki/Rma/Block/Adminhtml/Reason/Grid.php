<?php
namespace Riki\Rma\Block\Adminhtml\Reason;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    protected $reasonCollectionFactory;

    /**
     * @var \Riki\Rma\Model\Config\Source\Reason\Dueto
     */
    protected $dueTo;

    /**
     * Grid constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Riki\Rma\Model\Config\Source\Reason\Dueto $dueTo
     * @param \Riki\Rma\Model\ResourceModel\Reason\CollectionFactory $reasonCollectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Riki\Rma\Model\Config\Source\Reason\Dueto $dueTo,
        \Riki\Rma\Model\ResourceModel\Reason\CollectionFactory $reasonCollectionFactory,
        array $data = []
    )
    {
        $this->reasonCollectionFactory = $reasonCollectionFactory;
        $this->dueTo = $dueTo;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     *
     */
    protected function _construct()
    {
        parent::_construct();

        $this->setId('reasonGrid');
        $this->setDefaultSort('reasoncode_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->reasonCollectionFactory->create()->addActiveFilter();
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
            'code',
            [
                'header' => __('Reason Code'),
                'type' => 'number',
                'index' => 'code'
            ]
        );
        $this->addColumn(
            'description_en',
            [
                'header' => __('Description (EN)'),
                'index' => 'description_en'
            ]
        );
        $this->addColumn(
            'description_jp',
            [
                'header' => __('Description (JP)'),
                'index' => 'description_jp'
            ]
        );
        $this->addColumn(
            'due_to',
            [
                'header' => __('Due To'),
                'index' => 'due_to',
                'type' => 'options',
                'options' => $this->dueTo->toArray()
            ]
        );
        $this->addColumn(
            'sap_code',
            [
                'header' => __('Sap Code'),
                'index' => 'sap_code'
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
                            'base' => '*/*/edit',
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
     * @return $this
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('reason');
        $this->getMassactionBlock()->addItem(
            'delete',
            [
                'label' => __('Delete'),
                'url' => $this->getUrl('*/*/delete'),
                'confirm' => __('Are you sure?'),
            ]
        );
        return $this;
    }

    /**
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }

    /**
     * get row url
     * @param  object $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl(
            '*/*/edit',
            array('id' => $row->getId())
        );
    }
}