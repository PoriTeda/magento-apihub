<?php

namespace Riki\Rma\Block\Adminhtml\Refund;

class Grid extends \Magento\Rma\Block\Adminhtml\Rma\Grid
{
    /**
     * @var \Riki\Rma\Model\Config\Source\Rma\RefundStatus
     */
    protected $refundStatusSource;

    /**
     * @var \Riki\Rma\Model\Config\Source\RefundPayment
     */
    protected $refundPaymentSource;

    /**
     * @var \Riki\Rma\Model\Config\Source\Rma\Type
     */
    protected $rmaTypeSource;

    /**
     * @var \Riki\Shipment\Model\ResourceModel\Status\Options\Payment
     */
    protected $paymentStatusSource;

    /**
     * @var \Riki\Rma\Helper\Refund
     */
    protected $refundHelper;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resourceConnection;

    /**
     * Grid constructor.
     *
     * @param \Riki\Rma\Helper\Refund $refundHelper
     * @param \Riki\Shipment\Model\ResourceModel\Status\Options\Payment $paymentStatusSource
     * @param \Riki\Rma\Model\Config\Source\Rma\Type $rmaTypeSource
     * @param \Riki\Rma\Model\Config\Source\RefundPayment $refundPaymentSource
     * @param \Riki\Rma\Model\Config\Source\Rma\RefundStatus $refundStatusSource
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Rma\Model\ResourceModel\Rma\Grid\CollectionFactory $collectionFactory
     * @param \Magento\Rma\Model\RmaFactory $rmaFactory
     * @param array $data
     */
    public function __construct(
        \Riki\Rma\Helper\Refund $refundHelper,
        \Riki\Shipment\Model\ResourceModel\Status\Options\Payment $paymentStatusSource,
        \Riki\Rma\Model\Config\Source\Rma\Type $rmaTypeSource,
        \Riki\Rma\Model\Config\Source\RefundPayment $refundPaymentSource,
        \Riki\Rma\Model\Config\Source\Rma\RefundStatus $refundStatusSource,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Rma\Model\ResourceModel\Rma\Grid\CollectionFactory $collectionFactory,
        \Magento\Rma\Model\RmaFactory $rmaFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        array $data = []
    ) {
    
        $this->refundHelper = $refundHelper;
        $this->paymentStatusSource = $paymentStatusSource;
        $this->rmaTypeSource = $rmaTypeSource;
        $this->refundPaymentSource = $refundPaymentSource;
        $this->refundStatusSource = $refundStatusSource;
        $this->_resourceConnection = $resourceConnection;
        parent::__construct($context, $backendHelper, $collectionFactory, $rmaFactory, $data);
    }

    /**
     * {@inheritdoc}
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();


        if (!$this->_authorization->isAllowed('Riki_Rma::rma_refund_actions_export_csv')) {
            $this->unsetChild('export_button');
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return $this
     */
    protected function _prepareMassaction()
    {
        parent::_prepareMassaction();

        /** @var \Magento\Backend\Block\Widget\Grid\Massaction\Extended $massActionBlock */
        $massActionBlock = $this->getMassactionBlock();
        if (!$massActionBlock) {
            return $this;
        }
        $this->_exportTypes = [];
        $this->addExportType('riki_rma/refund_export/csv', __('CSV'));

        $massActionBlock->removeItem('status');

        if ($this->getAuthorization()->isAllowed('Riki_Rma::rma_refund_actions_deny')) {
            $massActionBlock->addItem('deny', [
                'label' => __('Reject (adjustment needed)'),
                'url' => $this->getUrl('riki_rma/refund/deny'),
                'confirm' => __('Are you sure [%1] these item(s)?', __('Reject (adjustment needed)'))
            ]);
        }
        if ($this->getAuthorization()->isAllowed('Riki_Rma::rma_refund_actions_reject')) {
            $massActionBlock->addItem('reject', [
                'label' => __('Reject (No need refund)'),
                'url' => $this->getUrl('riki_rma/refund/reject'),
                'confirm' => __('Are you sure [%1] these item(s)?', __('Reject (No need refund)'))
            ]);
        }
        if ($this->getAuthorization()->isAllowed('Riki_Rma::rma_refund_actions_approve')) {
            $massActionBlock->addItem('approve', [
                'label' => __('Approve'),
                'url' => $this->getUrl('riki_rma/refund/approve'),
                'confirm' => __('Are you sure [Approve] these item(s)?')
            ]);
        }
        if ($this->getAuthorization()->isAllowed('Riki_Rma::rma_refund_actions_processing_bank')) {
            $massActionBlock->addItem('bank_processing', [
                'label' => __('Processing by Bank transfer'),
                'url' => $this->getUrl('riki_rma/refund_bank/processing'),
                'confirm' => __('Are you sure [Processing by Bank transfer] these item(s)?')
            ]);
        }
        if ($this->getAuthorization()->isAllowed('Riki_Rma::rma_refund_actions_processing_check')) {
            $massActionBlock->addItem('check_processing', [
                'label' => __('Processing by Check issue'),
                'url' => $this->getUrl('riki_rma/refund_check/processing'),
                'confirm' => __('Are you sure [Processing by Check issue] these item(s)?')
            ]);
        }
        if ($this->getAuthorization()->isAllowed('Riki_Rma::rma_refund_actions_complete_bank')) {
            $massActionBlock->addItem('bank_complete', [
                'label' => __('Complete by Bank transfer'),
                'url' => $this->getUrl('riki_rma/refund_bank/complete'),
                'confirm' => __('Are you sure [Complete by Bank transfer] these item(s)?')
            ]);
        }
        if ($this->getAuthorization()->isAllowed('Riki_Rma::rma_refund_actions_complete_check')) {
            $massActionBlock->addItem('check_complete', [
                'label' => __('Complete by Check issue'),
                'url' => $this->getUrl('riki_rma/refund_check/complete'),
                'confirm' => __('Are you sure [Complete by Check issue] these item(s)?')
            ]);
        }
        if ($this->getAuthorization()->isAllowed(
            'Riki_Rma::rma_refund_actions_card_complete_check'
        )) {
            $massActionBlock->addItem('complete_card_check', [
                'label' => __('Complete by Manually card complete'),
                'url' => $this->getUrl('riki_rma/refund_check/cardComplete'),
                'confirm' => __('Are you sure [Complete by Manually card complete] these item(s)?')
            ]);
        }

        return $this;
    }


    /**
     * {@inheritdoc}
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        parent::_prepareColumns();

        $this->removeColumn('date_requested');
        $this->removeColumn('action');

        $incrementIdCol = $this->getColumn('increment_id');
        if ($incrementIdCol) {
            $incrementIdCol
                ->setData('header', __('RMA number'))
                ->setData('renderer', \Riki\Rma\Block\Adminhtml\Rma\Grid\Column\Renderer\RmaIncrementId::class);
        }

        $orderIncrementIdCol = $this->getColumn('order_increment_id');
        if ($orderIncrementIdCol) {
            $orderIncrementIdCol->setData('header', __('Order number'));
        }

        $this->addColumn('refund_status', [
            'header' => __('Refund status'),
            'index' => 'refund_status',
            'type' => 'options',
            'options' => $this->refundStatusSource->toArray()
        ]);

        $this->addColumn('refund_method', [
            'header' => __('Refund method'),
            'index' => 'refund_method',
            'type' => 'options',
            'options' => $this->refundPaymentSource->toArray(),
            'renderer' => \Riki\Rma\Block\Adminhtml\Rma\Grid\Column\Renderer\RefundMethod::class,
        ]);

        $this->addColumn('total_return_amount_adjusted', [
            'header' => __('Refund amount'),
            'index' => 'total_return_amount_adjusted',
            'type' => 'text'
        ]);

        $paymentMethods = [];
        foreach ($this->refundHelper->getEnablePaymentMethods() as $value => $method) {
            $paymentMethods[$value] = $method['title'];
        }
        $this->addColumn('payment_method', [
            'header' => __('Payment method'),
            'type' => 'options',
            'options' => $paymentMethods,
            'renderer' => \Riki\Rma\Block\Adminhtml\Rma\Grid\Column\Renderer\OrderPaymentStatus::class,
        ]);

        $this->addColumn('return_approval_date', [
            'header' => __('Date of return approved'),
            'index' => 'return_approval_date',
            'type' => 'date',
            'timezone' => true,
            'filter' => \Riki\Rma\Block\Widget\Grid\Column\Filter\Date::class
        ]);

        $this->addColumn('creditmemo_increment_id', [
            'header' => __('Credit memo'),
            'index' => 'creditmemo_increment_id',
            'type' => 'action',
            'renderer' => \Riki\Rma\Block\Adminhtml\Rma\Grid\Column\Renderer\Creditmemo::class
        ]);

        $this->addColumn('updated_at', [
            'header' => __('Last updated'),
            'index' => 'updated_at',
            'type' => 'date'
        ]);

        $this->addColumn('customer_id', [
            'header' => __('Customer ID'),
            'index' => 'customer_id',
            'type' => 'text'
        ]);

        $this->addColumn('consumer_db_id', [
            'header' => __('Consumer DB ID'),
            'index' => 'consumer_db_id',
            'type' => 'text'
        ]);

        $this->addColumn('full_partial', [
            'header' => __('Full / partial'),
            'index' => 'full_partial',
            'type' => 'options',
            'options' => $this->rmaTypeSource->toArray()
        ]);

        $this->addColumn('payment_date', [
            'header' => __('Payment date'),
            'type' => 'date',
            'index' => 'payment_date'
        ]);

        $this->addColumn('payment_agent', [
            'header' => __('Payment agent'),
            'type' => 'text',
            'index' => 'payment_agent'
        ]);

        $this->addColumn('payment_status', [
            'header' => __('Payment status'),
            'type' => 'options',
            'index' => 'payment_status',
            'options' => $this->paymentStatusSource->toArray()
        ]);

        $this->addColumn('rma_shipment_number', [
            'header' => __('Shipment number'),
            'index' => 'rma_shipment_number',
            'type' => 'text'
        ]);

        $this->addColumn('substitution_order', [
            'header' => __('Substitution order'),
            'index' => 'substitution_order',
            'type' => 'text'
        ]);
        $this->addColumnsOrder('order_increment_id', 'increment_id');
        $this->addColumnsOrder('return_status', 'order_increment_id');
        $this->addColumnsOrder('returned_date', 'return_status');
        $this->addColumnsOrder('reason_id', 'returned_date');
        $this->addColumnsOrder('payment_status', 'reason_id');
        $this->addColumnsOrder('refund_status', 'payment_status');
        $this->addColumnsOrder('refund_method', 'refund_status');
        $this->addColumnsOrder('total_return_amount_adjusted', 'refund_method');
        $this->addColumnsOrder('payment_method', 'total_return_amount_adjusted');
        $this->addColumnsOrder('return_approval_date', 'payment_method');
        $this->addColumnsOrder('full_partial', 'return_approval_date');
        $this->addColumnsOrder('payment_date', 'full_partial');
        $this->addColumnsOrder('payment_agent', 'payment_date');
        $this->addColumnsOrder('substitution_order', 'payment_agent');
        $this->addColumnsOrder('rma_shipment_number', 'substitution_order');
        $this->addColumnsOrder('order_type', 'rma_shipment_number');
        $this->addColumnsOrder('membership', 'order_type');
        $this->addColumnsOrder('customer_name', 'membership');
        $this->addColumnsOrder('creditmemo_increment_id', 'customer_name');
        $this->addColumnsOrder('updated_at', 'creditmemo_increment_id');
        $this->addColumnsOrder('customer_id', 'updated_at');
        $this->addColumnsOrder('consumer_db_id', 'customer_id');
        $this->addColumnAfter('email', [
            'header' => __('Email'),
            'index' => 'email',
            'type' => 'text',
            'renderer' => \Riki\Rma\Block\Adminhtml\Rma\Grid\Column\Renderer\Email::class,
            'filter' => false,
            'column_css_class'=>'no-display',
            'header_css_class'=>'no-display'
        ], 'customer_name');
        $this->addColumnAfter('telephone', [
            'header' => __('Phone number'),
            'index' => 'telephone',
            'type' => 'text',
            'renderer' => \Riki\Rma\Block\Adminhtml\Rma\Grid\Column\Renderer\Telephone::class,
            'filter' => false,
            'column_css_class'=>'no-display',
            'header_css_class'=>'no-display'
        ], 'email');
        $this->addColumnAfter('order_date', [
            'header' => __('Order Date'),
            'index' => 'order_date',
            'type' => 'date',
            'column_css_class'=>'no-display',
            'header_css_class'=>'no-display'
        ], 'order_increment_id');
        return $this;
    }


    /**
     * {@inheritdoc}
     *
     * @param \Magento\Framework\Data\Collection $collection
     */
    public function setCollection($collection)
    {
        $collection->addFieldToFilter('refund_status', ['notnull' => true])
            ->addFieldToFilter('total_return_amount_adjusted', ['gt'    =>  0])
            ->addFieldToFilter('refund_allowed', 1);
        $collection = $this->filterOrderPaymentStatus($collection);
        parent::setCollection($collection);
    }

    /**
     * {@inheritdoc}
     *
     * @param \Magento\Framework\DataObject $row
     *
     * @return bool
     */
    public function getRowUrl($row)
    {
        return false;
    }

    /**
     * Filter value for order payment status
     *
     * @param $collection
     * @return mixed
     */
    public function filterOrderPaymentStatus($collection)
    {
        $filter = $this->getParam($this->getVarNameFilter(), null);
        if (is_string($filter)) {
            $data = $this->_backendHelper->prepareFilterString($filter);
            if (isset($data['payment_method']) && $data['payment_method'] !=null) {
                $paymentMethod = trim($data['payment_method']);
                $orderId = $collection->getColumnValues('order_id');
                if (is_array($orderId)) {
                    $connection = $this->_resourceConnection->getConnection('sales');
                    $select = $connection->select()
                        ->from([$connection->getTableName('sales_order_payment')], ['parent_id'])
                        ->where("parent_id IN (" . implode(',', $orderId) . ") AND method = '$paymentMethod' ");
                    $arrIds = $connection->fetchCol($select);

                    if (is_array($arrIds)) {
                        $collection->addFieldToFilter('order_id', $arrIds);
                    }
                }
            }
        }

        return $collection;
    }
}
