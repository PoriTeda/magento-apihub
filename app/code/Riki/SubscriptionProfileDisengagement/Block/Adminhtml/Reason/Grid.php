<?php
namespace Riki\SubscriptionProfileDisengagement\Block\Adminhtml\Reason;
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    protected $_reasonCollectionFactory;
    /**
     * @var \Riki\SubscriptionProfileDisengagement\Helper\Data
     */
    protected $disengagementHelper;

    /**
     * Grid constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Riki\SubscriptionProfileDisengagement\Helper\Data $disengagementHelper
     * @param \Riki\SubscriptionProfileDisengagement\Model\ResourceModel\Reason\CollectionFactory $reasonCollectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Riki\SubscriptionProfileDisengagement\Helper\Data $disengagementHelper,
        \Riki\SubscriptionProfileDisengagement\Model\ResourceModel\Reason\CollectionFactory $reasonCollectionFactory,
        array $data = []
    )
    {
        $this->_reasonCollectionFactory = $reasonCollectionFactory;
        $this->disengagementHelper = $disengagementHelper;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     *
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('reasonGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->_reasonCollectionFactory->create()->addActiveFilter();
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
            'visibility',
            [
                'header' => __('Visibility'),
                'type' => 'options',
                'options' => $this->disengagementHelper->getVisibilityOptions(),
                'index' => 'visibility'
            ]
        );

        $this->addColumn(
            'description_en',
            [
                'header' => __('Title'),
                'index' => 'title'
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
                'url' => $this->getUrl('*/*/massDelete'),
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