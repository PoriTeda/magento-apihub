<?php
namespace Riki\Rma\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Riki\Rma\Api\Data\Rma\TypeInterface;
use Riki\Rma\Api\Data\Rma\ReturnStatusInterface;
use Riki\Shipment\Model\ResourceModel\Status\Options\Payment;
use Bluecom\Paygent\Model\Paygent;

class ValidateData implements ObserverInterface
{
    protected $returnWithoutGoodsReasonId;

    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $searchHelper;

    /**
     * @var \Magento\Sales\Api\ShipmentRepositoryInterface
     */
    protected $shipmentRepository;

    /**
     * @var \Riki\Rma\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Riki\Framework\Helper\Datetime
     */
    protected $datetimeHelper;

    /**
     * @var \Riki\Rma\Api\RmaRepositoryInterface
     */
    protected $rmaRepository;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Riki\Rma\Model\Config\Source\Rma\Type
     */
    protected $typeSource;

    /**
     * Validate constructor.
     *
     * @param \Riki\Rma\Model\Config\Source\Rma\Type $typeSource
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Riki\Rma\Api\ItemRepositoryInterface $rmaItemRepository
     * @param \Riki\Rma\Helper\Data $dataHelper
     * @param \Riki\Rma\Api\RmaRepositoryInterface $rmaRepository
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Riki\Framework\Helper\Datetime $datetimeHelper
     * @param \Riki\Rma\Model\Config\Source\Rma\Type $typeSource
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
     * @param \Riki\Sales\Helper\Order $orderHelper
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Riki\Rma\Api\ItemRepositoryInterface $rmaItemRepository,
        \Riki\Rma\Helper\Data $dataHelper,
        \Riki\Rma\Api\RmaRepositoryInterface $rmaRepository,
        \Riki\Framework\Helper\Search $searchHelper,
        \Riki\Framework\Helper\Datetime $datetimeHelper,
        \Riki\Rma\Model\Config\Source\Rma\Type $typeSource,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Riki\Sales\Helper\Order $orderHelper
    ) {
        $this->typeSource = $typeSource;
        $this->request = $request;
        $this->rmaItemRepository = $rmaItemRepository;
        $this->dataHelper = $dataHelper;
        $this->rmaRepository = $rmaRepository;
        $this->datetimeHelper = $datetimeHelper;
        $this->dataHelper = $dataHelper;
        $this->shipmentRepository = $shipmentRepository;
        $this->searchHelper = $searchHelper;
        $this->orderHelper = $orderHelper;
        $this->returnWithoutGoodsReasonId = $dataHelper->getReturnWithoutGoodsReasonId();
    }


    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /* @var $rma \Magento\Rma\Model\Rma */
        $rma = $observer->getRma();
        
        $data = $rma->getData();
        foreach ($data as $key => $value) {
            if (!$rma->dataHasChangedFor($key)) {
                continue;
            }
            $method = 'validate' . $this->_capitalize($key);
            if (method_exists($this, $method)) {
                $this->$method($rma);
            }
        }
    }

    /**
     * Transform from _ to ucfirst
     *
     * @param $key
     *
     * @return string
     */
    public function _capitalize($key)
    {
        return implode('', array_map('ucfirst', explode('_', strtolower($key))));
    }

    /**
     * Validate for rma_shipment_number
     *
     * @param \Magento\Rma\Model\Rma $rma
     *
     * @return void
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function validateRmaShipmentNumber(\Magento\Rma\Model\Rma $rma)
    {
        $shipmentNumber = $rma->getData('rma_shipment_number');
        // no need validate if reasonId = 60 and shipment number is null
        if ($rma->getReasonId() != $this->returnWithoutGoodsReasonId || mb_strlen($shipmentNumber) > 0) {
            $orderId = $rma->getData('order_id');

            $shipment = $this->searchHelper
                ->getByIncrementId($shipmentNumber)
                ->getOne()
                ->execute($this->shipmentRepository);

            if (!$shipment) {
                throw new \Magento\Framework\Exception\LocalizedException(__("The Shipment number doesn't exist"));
            }

            if ($shipment) {
                if ($orderId != $shipment->getOrderId()) {
                    $message = __("The Shipment %1 is not belong to this order", $shipmentNumber);
                    throw new \Magento\Framework\Exception\LocalizedException($message);
                }
            }
        }
    }

    /**
     * Validate for reason_id
     *
     * @param \Magento\Rma\Model\Rma $rma
     *
     * @return void
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function validateReasonId(\Magento\Rma\Model\Rma $rma)
    {
        $reasonId = $rma->getData('reason_id');
        if (!$reasonId) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Reason can’t be empty'));
        }

        if (!$this->dataHelper->getRmaReason($rma)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Reason code is invalid'));
        }
    }

    /**
     * Validate for returned_date
     *
     * @param \Magento\Rma\Model\Rma $rma
     *
     * @return void
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function validateReturnedDate(\Magento\Rma\Model\Rma $rma)
    {
        $returnedDate = date('Y-m-d', strtotime($rma->getData('returned_date')));

        if ($returnedDate > $this->datetimeHelper->getToday()->format('Y-m-d')) {
            throw new \Magento\Framework\Exception\LocalizedException(__(
                'The returned date should be today or before today'
            ));
        }
    }


    /**
     * Validate for full_partial
     *
     * @param \Magento\Rma\Model\Rma $rma
     *
     * @return void
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function validateFullPartial(\Magento\Rma\Model\Rma $rma)
    {
        if ($rma->getId() && !$rma->getData('skip_full_partial_validation_flag')) {
            throw new \Magento\Framework\Exception\LocalizedException(__('You can only update return type on create new return.'));
        }

        if ($rma->getData('skip_full_partial_validation_flag')){
            return;
        }

        /** @var \Magento\Rma\Model\ResourceModel\Item $itemResource */
        $itemResource = $this->rmaItemRepository->createFromArray()->getResource();
        $returnableItems = $itemResource->getReturnableItems($rma->getOrderId());
        foreach ($rma->getItems() as $item) {
            $orderItem = $this->dataHelper->getRmaItemOrderItem($item);
            if (!$orderItem) {
                continue;
            }

            if ($orderItem->getParentItemId() && isset($returnableItems[$orderItem->getParentItemId()])) {
                unset($returnableItems[$orderItem->getParentItemId()]);
            }

            if (isset($returnableItems[$orderItem->getId()])) {
                $returnableItems[$orderItem->getId()] = $returnableItems[$orderItem->getId()] - intval($item->getData('qty_requested'));
            }
        }

        $existRma = false;
        $rmaItems = $this->dataHelper->getOrderRmaItems($this->dataHelper->getRmaOrder($rma));
        foreach ($rmaItems as $rmaItem) {
            if ($rmaItem->getStatus() != \Magento\Rma\Model\Rma\Source\Status::STATE_REJECTED) {
                $existRma = true;
                break;
            }
        }

        if ($rma->getData('full_partial') == TypeInterface::FULL) {
            if ($existRma) {
                throw new \Magento\Framework\Exception\LocalizedException(__(
                    'You can not choose return type %1 because order %2 have already a return.',
                    '<strong>' . $this->typeSource->getLabel(TypeInterface::FULL) .'</strong>',
                    '<strong>' . $rma->getOrderIncrementId() . '</strong>'
                ));
            }

            if (array_sum($returnableItems)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('You can only choose return type %1 by selected all returnable items', '<strong>' . $this->typeSource->getLabel(TypeInterface::FULL) . '</strong>'));
            }
        } elseif ($rma->getData('full_partial') == TypeInterface::PARTIAL) {
            if (!$existRma && !array_sum($returnableItems)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('You can only choose return type %1 by selected part of returnable items', '<strong>' . $this->typeSource->getLabel(TypeInterface::PARTIAL) . '</strong>'));
            }
        }
    }

    /**
     * Validate for return_status
     *
     * @param \Magento\Rma\Model\Rma $rma
     *
     * @return void
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validateReturnStatus(\Magento\Rma\Model\Rma $rma)
    {
        $paymentValidateResult = $this->validateOrderPayment($rma);

        if ($paymentValidateResult !== true && is_string($paymentValidateResult)) {
            throw new \Magento\Framework\Exception\LocalizedException(__($paymentValidateResult));
        }

        if ($rma->getData('return_status') == ReturnStatusInterface::CS_FEEDBACK_REJECTED) {
            $comment = $this->request->getParam('comment');
            if (!isset($comment['comment']) || !$comment['comment']) {
                throw new \Magento\Framework\Exception\LocalizedException(__('You must set a comment to explain your rejection'));
            }
        }
    }

    /**
     * @param \Magento\Rma\Model\Rma $rma
     * @return bool|\Magento\Framework\Phrase
     */
    public function validateOrderPayment(\Magento\Rma\Model\Rma $rma){
        if ($rma->getData('return_status') == ReturnStatusInterface::REVIEWED_BY_CC &&
            !strlen((string)$rma->getData('substitution_order'))
        ) {
            $order = $this->dataHelper->getRmaOrder($rma);
            if (!$order instanceof \Magento\Sales\Model\Order
                || ($order->getPayment()->getMethod() == Paygent::CODE
                    && $order->getData('payment_status') != Payment::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED)
            ) {
                return 'The payment has not been collected on this order, the return can\'t be approved';
            }
        }
        return true;
    }

    /**
     * Validate for substitution_order
     *
     * @param \Magento\Rma\Model\Rma $rma
     *
     * @return \Magento\Rma\Model\Rma
     */
    public function validateSubstitutionOrder(\Magento\Rma\Model\Rma $rma)
    {
        $rma->setData('refund_allowed', 0);
        if (strlen((string)$rma->getData('substitution_order'))) {
            $rma->setData('is_exported_sap', \Riki\SapIntegration\Model\Api\Shipment::NO_NEED_TO_EXPORT);
        }

        return $rma;
    }

    /**
     * Validate for order data
     *      not allowed to create new return if this order is delay payment and not captured yet.
     *
     * @param \Magento\Rma\Model\Rma $rma
     *
     * @return void
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function validateOrderId(\Magento\Rma\Model\Rma $rma)
    {
        if ($rma->getId()) {
            return;
        }

        $order = $this->dataHelper->getRmaOrder($rma);
        if (!$order instanceof \Magento\Sales\Model\Order) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Order data is invalid.'));
        }

        if ($this->orderHelper->isDelayPaymentOrder($order)) {
            /*this order is delay payment and not allowed to create new return*/
            if (!$this->orderHelper->isDelayPaymentOrderAllowedReturn($order)) {
                throw new \Magento\Framework\Exception\LocalizedException(__(
                    'This order #%1 is used delay payment, is not allowed to create new return right now.',
                    $order->getIncrementId()
                ));
            }
        }

        return true;
    }

}
