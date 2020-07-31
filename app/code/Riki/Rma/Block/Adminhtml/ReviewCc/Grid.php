<?php

namespace Riki\Rma\Block\Adminhtml\ReviewCc;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Riki\Rma\Model\ReviewCcFactory
     */
    protected $reviewFactory;

    /**
     * Grid constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Riki\Rma\Model\ReviewCcFactory $reviewCcFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Riki\Rma\Model\ReviewCcFactory $reviewCcFactory,
        array $data = []
    ) {
        $this->reviewFactory = $reviewCcFactory;
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

        $this->setId('reviewCcGrid');
        $this->setDefaultSort('created_at');
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
            /** @var \Riki\Rma\Model\ReviewCc $reviewModel */
            $reviewModel = $this->reviewFactory->create();
            $collection = $reviewModel->getCollection();
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
                'header_css_class' => 'col-review-id',
                'column_css_class' => 'col-review-id'
            ]
        );

        $this->addColumn(
            'created_at',
            [
                'header' => __('Executed Date'),
                'index' => 'created_at',
                'type' => 'datetime',
                'html_decorators' => ['nobr'],
                'header_css_class' => 'col-period',
                'column_css_class' => 'col-period'
            ]
        );

        $this->addColumn(
            'executed_from',
            [
                'header' => __('Executed From'),
                'index' => 'executed_from',
                'type' => 'datetime',
                'html_decorators' => ['nobr'],
                'header_css_class' => 'col-period',
                'column_css_class' => 'col-period'
            ]
        );

        $this->addColumn(
            'executed_to',
            [
                'header' => __('Executed To'),
                'index' => 'executed_to',
                'type' => 'datetime',
                'html_decorators' => ['nobr'],
                'header_css_class' => 'col-period',
                'column_css_class' => 'col-period'
            ]
        );

        $this->addColumn(
            'executed_by',
            [
                'header' => __('Executed By User'),
                'index' => 'executed_by',
                'header_css_class' => 'col-name',
                'column_css_class' => 'col-name'
            ]
        );

        $this->addColumn(
            'total_returns',
            [
                'header' => __('Total Returns'),
                'index' => 'total_returns',
                'type'  =>  'number',
                'header_css_class' => 'col-name',
                'column_css_class' => 'col-name'
            ]
        );

        $this->addColumn(
            'total_success_returns',
            [
                'header' => __('Success Returns'),
                'index' => 'total_success_returns',
                'type'  =>  'number',
                'header_css_class' => 'col-name',
                'column_css_class' => 'col-name'
            ]
        );

        $this->addColumn(
            'total_failed_returns',
            [
                'header' => __('Failed Returns'),
                'index' => 'total_failed_returns',
                'type'  =>  'number',
                'header_css_class' => 'col-name',
                'column_css_class' => 'col-name'
            ]
        );

        $this->addColumn(
            'log_file',
            [
                'header' => __('Log File'),
                'type'  =>  'text',
                'filter' => false,
                'sortable' => false,
                'renderer' => \Riki\Rma\Block\Adminhtml\ReviewCc\Grid\Column\Renderer\LogFile::class,
                'header_css_class' => 'col-log-file',
                'column_css_class' => 'col-log-file'
            ]
        );

        /** @var $reviewModel \Riki\Rma\Model\ReviewCc */
        $reviewModel = $this->reviewFactory->create();
        $this->addColumn(
            'status',
            [
                'header' => __('Status'),
                'index' => 'status',
                'type' => 'options',
                'options' => $reviewModel->getAllStatuses(),
                'header_css_class' => 'col-status',
                'column_css_class' => 'col-status'
            ]
        );

        return parent::_prepareColumns();
    }
}
