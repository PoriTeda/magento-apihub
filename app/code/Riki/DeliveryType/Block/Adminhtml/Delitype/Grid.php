<?php


namespace Riki\DeliveryType\Block\Adminhtml\Delitype;



class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{

    protected $_deliCollectionFactory;


    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Riki\DeliveryType\Model\ResourceModel\Delitype\CollectionFactory $deliCollectionFactory,
        array $data = []
    ) {
        $this->_deliCollectionFactory = $deliCollectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * [_construct description].
     *
     * @return [type] [description]
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('deliGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    protected function _prepareCollection()
    {
       
        $collection = $this->_deliCollectionFactory->create();


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
                'filter' => false,
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
            ]
        );
        $this->addColumn(
            'name',
            [
                'header' => __('Delivery type'),
                'align' => 'left',
                'filter' => false,
                'index' => 'name',
            ]
        );
        $this->addColumn(
            'shipping_fee',
            [
                'header' => __('Fee per delivery type (JPY)'),
                'align' => 'left',
                'filter' => false,
                'index' => 'shipping_fee',
            ]
        );
        $this->addColumn(
            'sync_code',
            [
                'header' => __('Code Sync with 3PLWH'),
                'align' => 'left',
                'filter' => false,
                'index' => 'sync_code',
            ]
        );
        $this->addColumn(
            'code',
            [
                'header' => __('Code'),
                'align' => 'left',
                'filter' => false,
                'index' => 'code',
            ]
        );
        $this->addColumn(
            'description',
            [
                'header' => __('Description'),
                'align' => 'left',
                'filter' => false,
                'index' => 'description',
            ]
        );
        $this->addColumn(
            'edit',
            [
                'header' => __('Action'),
                'type' => 'action',
                'getter' => 'getId',
                'actions' => [
                    [
                        'caption' => __('Action'),
                        'url' => ['base' => '*/*/edit'],
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
        return $this->getUrl('*/*/grid', array('_current' => true, 'action' => $this->getRequest()->getActionName()));
    }
}
