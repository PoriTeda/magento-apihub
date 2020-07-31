<?php
namespace Riki\Rma\Plugin\Rma\Block\Adminhtml\Rma\Edit\Tab\General;

use Riki\Rma\Helper\Constant;
use Riki\Rma\Api\Data\Rma\ReturnStatusInterface;
use Magento\OfflinePayments\Model\Cashondelivery;
use Riki\NpAtobarai\Model\Payment\NpAtobarai;

class Details
{
    /**
     * @var \Riki\Rma\Api\RmaRepositoryInterface
     */
    protected $rmaRepository;

    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $searchHelper;

    /**
     * @var \Riki\Rma\Model\Config\Source\Reason
     */
    protected $reasonSource;

    /**
     * @var \Riki\Rma\Model\Config\Source\Rma\Warehouse
     */
    protected $warehouseSource;

    /**
     * @var \Riki\Rma\Helper\Data
     */
    protected $dataHelper;

    /**
     * Details constructor.
     *
     * @param \Riki\Rma\Api\RmaRepositoryInterface $rmaRepository
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Riki\Rma\Helper\Data $dataHelper
     * @param \Riki\Rma\Model\Config\Source\Rma\Warehouse $warehouseSource
     * @param \Riki\Rma\Model\Config\Source\Reason $reasonSource
     */
    public function __construct(
        \Riki\Rma\Api\RmaRepositoryInterface $rmaRepository,
        \Riki\Framework\Helper\Search $searchHelper,
        \Riki\Rma\Helper\Data $dataHelper,
        \Riki\Rma\Model\Config\Source\Rma\Warehouse $warehouseSource,
        \Riki\Rma\Model\Config\Source\Reason $reasonSource
    ) {
        $this->rmaRepository = $rmaRepository;
        $this->searchHelper = $searchHelper;
        $this->dataHelper = $dataHelper;
        $this->warehouseSource = $warehouseSource;
        $this->reasonSource = $reasonSource;
    }

    /**
     * Extend setLayout()
     *
     * @param \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Details $subject
     * @param \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Details $result
     * @return mixed
     */
    public function afterSetLayout(
        \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Details $subject,
        \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Details $result
    ) {
        $this->prepareReasonId($result);
        $this->prepareReturnedWarehouse($result);
        $this->prepareShipmentNumber($result);
        $this->prepareFullPartial($result);
        $this->prepareSubstitutionOrder($result);

        return $result;
    }

    /**
     * Prepare Shipment Number data
     *
     * @param \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Details $block
     */
    protected function prepareShipmentNumber(\Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Details $block)
    {
        if ($block->hasData(Constant::REGISTRY_KEY_RMA_SHIPMENT_NUMBER)) {
            return;
        }

        $order = $block->getOrder();
        if (!$order instanceof \Magento\Sales\Model\Order) {
            return;
        }

        $payment = $order->getPayment();
        if (!$payment instanceof \Riki\Sales\Model\Order\Payment) {
            return;
        }
        
        if ($payment->getMethod() != Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE &&
            $payment->getMethod() != NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE
        ) {
            return;
        }

        $block->setData(Constant::REGISTRY_KEY_RMA_SHIPMENT_NUMBER, true);
    }

    /**
     * Prepare Reason Id Data
     *
     * @param \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Details $block
     */
    protected function prepareReasonId(\Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Details $block)
    {
        if ($block->hasData(Constant::REGISTRY_KEY_REASON_ID)) {
            return;
        }

        $data = [
            'options' => ['' => ' '] + $this->reasonSource->toArray()
        ];
        $rma = $this->dataHelper->getCurrentRma();
        if ($rma && !isset($data['options'][$rma->getData('reason_id')])) {
            $reason = $this->dataHelper->getRmaReason($rma);
            if ($reason) {
                $data['options'][$rma->getData('reason_id')] = $reason->getData('code') . ' - ' . $reason->getDescription();
            }
        }

        $block->setData(Constant::REGISTRY_KEY_REASON_ID, $data);
    }

    /**
     * Prepare Returned Warehouse Data
     *
     * @param \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Details $block
     */
    protected function prepareReturnedWarehouse(\Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Details $block)
    {
        if ($block->hasData(Constant::REGISTRY_KEY_RETURNED_WAREHOUSE)) {
            return;
        }

        $data = [
            'options' => ['' => ' '] + $this->warehouseSource->toArray()
        ];

        $block->setData(Constant::REGISTRY_KEY_RETURNED_WAREHOUSE, $data);
    }

    /**
     * Prepare Full Partial Data
     *
     * @param \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Details $block
     */
    protected function prepareFullPartial(\Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Details $block)
    {
        if ($block->hasData(Constant::REGISTRY_KEY_FULL_PARTIAL)) {
            return;
        }

        $data = [
            'options' => [
                '' => ' ',
                \Riki\Rma\Api\Data\Rma\TypeInterface::FULL => __('Full'),
                \Riki\Rma\Api\Data\Rma\TypeInterface::PARTIAL => __('Partial'),
            ]
        ];

        if (!$this->dataHelper->getCurrentRma()) {
            $rmaItems = $this->dataHelper->getOrderRmaItems($block->getOrder());
            foreach ($rmaItems as $rmaItem) {
                if ($rmaItem->getStatus() != \Magento\Rma\Model\Rma\Source\Status::STATE_REJECTED) {
                    unset($data['options'][\Riki\Rma\Model\Config\Source\Rma\Type::FULL]);
                    break;
                }
            }
        }

        $block->setData(Constant::REGISTRY_KEY_FULL_PARTIAL, $data);
    }

    /**
     * Prepare substitution_order
     *
     * @param \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Details $block
     * @return \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Details
     */
    public function prepareSubstitutionOrder(\Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Details $block)
    {
        if ($block->hasData(Constant::REGISTRY_KEY_SUBSTITUTION_ORDER)) {
            return $block;
        }

        if ($this->dataHelper->getCurrentRma()) {
            $block->setData(Constant::REGISTRY_KEY_SUBSTITUTION_ORDER, true);
        }

        return $block;
    }
}