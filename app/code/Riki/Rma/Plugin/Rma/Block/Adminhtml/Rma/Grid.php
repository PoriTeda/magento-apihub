<?php
namespace Riki\Rma\Plugin\Rma\Block\Adminhtml\Rma;

class Grid
{
    /**
     * @var \Riki\SubscriptionMembership\Model\Customer\Attribute\Source\Membership
     */
    protected $customerMembership;

    /**
     * @var \Riki\Rma\Model\Config\Source\Rma\ReturnStatus
     */
    protected $returnStatusSource;

    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $searchHelper;

    /**
     * @var \Riki\Rma\Api\ReasonRepositoryInterface
     */
    protected $reasonRepository;

    /**
     * @var \Riki\SapIntegration\Helper\Data
     */
    protected $sapHelper;

    /**
     * @var \Riki\Shipment\Model\ResourceModel\Status\Options\Payment
     */
    protected $paymentStatusOptions;

    /**
     * @var \Riki\SubscriptionCourse\Model\Course\Type
     */
    protected $orderTypeOptions;

    /**
     * @var \Riki\Rma\Helper\Refund
     */
    protected $refundHelper;

    /**
     * @var \Riki\Rma\Helper\Amount
     */
    protected $amountHelper;

    /**
     * @var \Riki\Rma\Model\Config\Source\Rma\MassAction
     */
    protected $massActionOptions;
    /**
     * @var \Riki\Rma\Model\Config\Source\Rma\Type
     */
    protected $rmaTypeSource;

    /**
     * @var \Magento\Config\Model\Config\Source\Yesno
     */
    protected $sourceYesNo;

    /**
     * Grid constructor.
     * @param \Riki\Rma\Api\ReasonRepositoryInterface $reasonRepository
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Riki\Rma\Model\Config\Source\Rma\ReturnStatus $returnStatusSource
     * @param \Riki\SubscriptionMembership\Model\Customer\Attribute\Source\Membership $customerMembership
     * @param \Riki\Shipment\Model\ResourceModel\Status\Options\Payment $paymentStatusOptions
     * @param \Riki\SubscriptionCourse\Model\Course\Type $orderTypeOptions
     * @param \Riki\Rma\Helper\Refund $refundHelper
     * @param \Riki\SapIntegration\Helper\Data $sapHelper
     * @param \Riki\Rma\Helper\Amount $amountHelper
     * @param \Riki\Rma\Model\Config\Source\Rma\MassAction $massActionOptions
     * @param \Riki\Rma\Model\Config\Source\Rma\Type $rmaTypeSource
     * @param \Magento\Config\Model\Config\Source\Yesno $sourceYesNo
     */
    public function __construct(
        \Riki\Rma\Api\ReasonRepositoryInterface $reasonRepository,
        \Riki\Framework\Helper\Search $searchHelper,
        \Riki\Rma\Model\Config\Source\Rma\ReturnStatus $returnStatusSource,
        \Riki\SubscriptionMembership\Model\Customer\Attribute\Source\Membership $customerMembership,
        \Riki\Shipment\Model\ResourceModel\Status\Options\Payment $paymentStatusOptions,
        \Riki\SubscriptionCourse\Model\Course\Type $orderTypeOptions,
        \Riki\Rma\Helper\Refund $refundHelper,
        \Riki\SapIntegration\Helper\Data $sapHelper,
        \Riki\Rma\Helper\Amount $amountHelper,
        \Riki\Rma\Model\Config\Source\Rma\MassAction $massActionOptions,
        \Riki\Rma\Model\Config\Source\Rma\Type $rmaTypeSource,
        \Magento\Config\Model\Config\Source\Yesno $sourceYesNo
    ) {
        $this->reasonRepository = $reasonRepository;
        $this->searchHelper = $searchHelper;
        $this->customerMembership = $customerMembership;
        $this->returnStatusSource = $returnStatusSource;
        $this->sapHelper = $sapHelper;
        $this->paymentStatusOptions = $paymentStatusOptions;
        $this->refundHelper = $refundHelper;
        $this->orderTypeOptions = $orderTypeOptions;
        $this->amountHelper = $amountHelper;
        $this->massActionOptions = $massActionOptions;
        $this->rmaTypeSource = $rmaTypeSource;
        $this->sourceYesNo = $sourceYesNo;
    }

    /**
     * Extend getMassactionIdFilter()
     *
     * @param \Magento\Rma\Block\Adminhtml\Rma\Grid $subject
     * @return array
     */
    public function beforeGetMassactionIdFilter(\Magento\Rma\Block\Adminhtml\Rma\Grid $subject)
    {
        if ($subject->getColumnCount() && !$subject->getColumn('order_type')) {
            $this->prepareMassAction($subject);
            $this->prepareColumns($subject);
            /** @var \Magento\\Rma\\Model\\ResourceModel\\Rma\\Grid\\Collection $collection */
            $collection = $subject->getPreparedCollection();
            /** @var \Magento\Framework\DB\Select $select */
            $select = $collection->getSelect();
            if (!$select->getPart(\Magento\Framework\DB\Select::ORDER)) {
                $select->order('returned_date ' . $collection::SORT_ORDER_DESC);
                $select->order('refund_method ' . $collection::SORT_ORDER_ASC);
                $select->order('return_status ' . $collection::SORT_ORDER_ASC);
            }
        }

        return [];
    }

    /**
     * Customize columns
     *
     * @param \Magento\Rma\Block\Adminhtml\Rma\Grid $block
     */
    public function prepareColumns(\Magento\Rma\Block\Adminhtml\Rma\Grid $block)
    {
        $block->removeColumn('status');

        $block->removeColumn('increment_id');

        $block->addColumn(
            'increment_id',
            [
                'header' => __('RMA'),
                'index' => 'increment_id',
                'type' => 'text',
                'header_css_class' => 'col-rma-number',
                'column_css_class' => 'col-rma-number',
                'filter_condition_callback' => [$this, '_filterIncrementIdCondition'],
            ]
        );

        $block->addColumn('order_type', [
            'header' => __('Order type'),
            'index' => 'order_type',
            'type' => 'options',
            'options' => $this->orderTypeOptions->getAllOrderTypeOptions()
        ]);
        $block->addColumn('membership', [
            'header' => __('Membership'),
            'index' => 'customer_type',
            'type' => 'options',
            'options' => $this->getCustomerMembershipOptions(),
            'renderer'  => \Riki\Rma\Block\Adminhtml\Rma\Grid\Column\Renderer\CustomerType::class,
            'filter_condition_callback' => [$this, '_filterCustomerTypeCondition'],
            'filter' => \Riki\Rma\Block\Widget\Grid\Column\Filter\Multiple::class
        ]);
        $block->addColumn('return_status', [
            'header' => __('Return status'),
            'index' => 'return_status',
            'type' => 'options',
            'options' => $this->returnStatusSource->toArray()
        ]);
        $block->addColumn('returned_date', [
            'header' => __('Return date'),
            'index' => 'returned_date',
            'type' => 'date',
            'filter' => \Riki\Rma\Block\Widget\Grid\Column\Filter\Date::class
        ]);
        $block->addColumn('reason_id', [
            'header' => __('Return reason'),
            'index' => 'reason_id',
            'type' => 'options',
            'options' => $this->getReasonOptions(),
            'filter_condition_callback' => [$this, '_filterReturnReasonCondition'],
            'filter' => \Riki\Rma\Block\Widget\Grid\Column\Filter\Multiple::class
        ]);

        if (!$block instanceof \Riki\Rma\Block\Adminhtml\Refund\Grid) {
            $block->addColumn('payment_status', [
                'header' => __('Payment Status'),
                'index' => 'payment_status',
                'type' => 'options',
                'options' => $this->paymentStatusOptions->toArray()
            ]);
            $block->addColumn('is_exported_sap', [
                'header' => __('Exported SAP'),
                'index' => 'is_exported_sap',
                'type' => 'options',
                'options' => $this->sapHelper->getFlagOptions()
            ]);
            $block->addColumn('export_sap_date', [
                'header' => __('Exported SAP Date'),
                'index' => 'export_sap_date'
            ]);
            $block->addColumn('comment', [
                'header' => __('Comment'),
                'index' => 'comment',
                'renderer' => \Riki\Rma\Block\Adminhtml\Rma\Grid\Column\Renderer\Comment::class,
                'filter_condition_callback' => [$this, '_filterCommentCondition']
            ]);
            $paymentMethods = [];
            foreach ($this->refundHelper->getEnablePaymentMethods() as $value => $method) {
                $paymentMethods[$value] = $method['title'];
            }
            $block->addColumn('payment_method', [
                'header' => __('Payment method'),
                'index' => 'payment_method',
                'type' => 'options',
                'options' => $paymentMethods,
                'renderer' => \Riki\Rma\Block\Adminhtml\Rma\Grid\Column\Renderer\OrderPaymentStatus::class,
            ]);
            $block->addColumn('refund_allowed', [
                'header' => __('Refund Allowed'),
                'index' => 'refund_allowed',
                'type' => 'options',
                'options' => ['No', 'Yes', 'N/A'],
                'renderer' => \Riki\Rma\Block\Adminhtml\Rma\Grid\Column\Renderer\RefundAllowed::class,
                'filter_condition_callback' => [$this, '_filterRefundAllowedCondition']
            ]);

            $block->addColumn(
                'full_partial',
                [
                    'header' => __('Partial/Full'),
                    'index' => 'full_partial',
                    'type' => 'options',
                    'options' => $this->rmaTypeSource->toArray()
                ]
            );
            $block->addColumn(
                'consumer_db_id',
                [
                    'header' => __('Consumer DB ID'),
                    'index' => 'consumer_db_id',
                    'type' => 'text'
                ]
            );

            $block->addColumnsOrder('increment_id', 'date_requested');
            $block->addColumnsOrder('date_requested', 'order_increment_id');
            $block->addColumnsOrder('order_increment_id', 'date_requested');
            $block->addColumnsOrder('consumer_db_id', 'customer_name');
            $block->addColumnsOrder('order_type', 'consumer_db_id');
            $block->addColumnsOrder('membership', 'order_type');
            $block->addColumnsOrder('return_status', 'membership');
            $block->addColumnsOrder('returned_date', 'return_status');
            $block->addColumnsOrder('reason_id', 'returned_date');
            $block->addColumnsOrder('payment_status', 'reason_id');
            $block->addColumnsOrder('export_sap_date', 'payment_status');
            $block->addColumnsOrder('is_exported_sap', 'export_sap_date');
            $block->addColumnsOrder('comment', 'is_exported_sap');
            $block->addColumnsOrder('payment_method', 'comment');
            $block->addColumnsOrder('refund_allowed', 'payment_method');
            $block->addColumnsOrder('full_partial', 'refund_allowed');
        }

        $block->sortColumnsByOrder();
    }

    /**
     * Condition filter customer_type
     *
     * @param $collection
     * @param $column
     */
    public function _filterCustomerTypeCondition($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return;
        }
        $values = explode(',', (string)($value));
        if (is_array($values)) {
            foreach ($values as $_val) {
                $collection->addFieldToFilter('customer_type', ['finset' => $_val]);
            }
        }
    }

    /**
     * Condition filter return reason
     *
     * @param $collection
     * @param $column
     */
    public function _filterReturnReasonCondition($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return;
        }
        $values = explode(',', (string)($value));
        $collection->addFieldToFilter('reason_id', ['in' => $values]);
    }

    /**
     * Condition filter increment id
     *
     * @param $collection
     * @param $column
     */
    public function _filterIncrementIdCondition($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return;
        }
        $value = str_replace(" ", "", $value);
        $values = explode(';', (string)($value));
        $collection->addFieldToFilter('increment_id', ['in' => $values]);
    }

    /**
     * Condition filter customer_type
     *
     * @param $collection
     * @param $column
     */
    public function _filterCommentCondition($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return;
        }
        $filter = ['like' => '%' . $value. '%'];
        $collection->addFieldToFilter('comment', $filter);
    }

    /**
     * Get customer membership
     *
     * @return array
     */
    public function getCustomerMembershipOptions()
    {
        $result = [];

        foreach ($this->customerMembership->getAllOptions() as $item) {
            $result[$item['value']] = $item['label'];
        }
        unset($result['']);

        return $result;
    }

    /**
     * Customize mass action block
     *
     * @param \Magento\Rma\Block\Adminhtml\Rma\Grid $block
     * @return \Magento\Rma\Block\Adminhtml\Rma\Grid
     */
    public function prepareMassAction(\Magento\Rma\Block\Adminhtml\Rma\Grid $block)
    {
        if (!$block instanceof \Riki\Rma\Block\Adminhtml\Refund\Grid) {
            /** @var \Magento\Backend\Block\Widget\Grid\Massaction\Extended $massActionBlock */
            $massActionBlock = $block->getMassactionBlock();
            if (!$massActionBlock) {
                return $block;
            }
            $massActionBlock->removeItem('status');

            $massActionBlock->removeItem('status');

            $authorization = $block->getAuthorization();

            foreach ($this->massActionOptions->optionList() as $key => $option) {
                if ($authorization->isAllowed($option['resource'])) {
                    if (isset($option['url'])) {
                        $url = $option['url'];
                    } else {
                        $url = 'riki_rma/returns/massAction';
                    }

                    $massActionBlock->addItem($key, [
                        'label' => $option['label'],
                        'url' => $block->getUrl($url, ['action'    =>  $key]),
                        'confirm' => __('Are you sure [%1] these item(s)?', $option['label'])
                    ]);
                }
            }
        }

        return $block;
    }

    /**
     * Get reason options
     *
     * @return array
     */
    protected function getReasonOptions()
    {
        $options = [];
        $reasons = $this->searchHelper
            ->getAll()
            ->execute($this->reasonRepository);
        foreach ($reasons as $reason) {
            $options[$reason->getId()] = $reason->getCode() . ' - ' . $reason->getDescription();
        }

        return $options;
    }

    /**
     * Condition filter refund allowed
     * @param $collection
     * @param $column
     */
    public function _filterRefundAllowedCondition($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        if ($value==2) {
            $collection->addFieldToFilter('refund_allowed', ['null' => true]);
        } else {
            $collection->addFieldToFilter('refund_allowed', ['eq' => $value]);
        }
    }

    /**
     * @param \Magento\Rma\Block\Adminhtml\Rma\Grid $subject
     * @param \Magento\Framework\View\LayoutInterface $layout
     * @return array
     */
    public function beforeSetLayout(
        \Magento\Rma\Block\Adminhtml\Rma\Grid $subject,
        \Magento\Framework\View\LayoutInterface $layout
    ) {
    
        $subject->addExportType('riki_rma/returns/exportCsv', __('CSV'));
        return [$layout];
    }
}
