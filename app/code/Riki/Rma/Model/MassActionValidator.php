<?php
namespace Riki\Rma\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Rma\Model\ResourceModel\Rma\Collection;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\Payment;
use Riki\Rma\Api\Data\Rma\ReturnStatusInterface;
use Riki\Rma\Helper\Status;
use Riki\Rma\Model\Config\Source\Rma\MassAction as MassActionOption;
use Riki\NpAtobarai\Model\Payment\NpAtobarai;
use Riki\NpAtobarai\Model\Config\Source\TransactionPaymentStatus;

/**
 * Class MassActionValidator
 * @package Riki\Rma\Model
 */
class MassActionValidator
{
    /**
     * @var Status
     */
    protected $statusHelper;

    /**
     * @var \Magento\Rma\Api\RmaRepositoryInterface
     */
    protected $rmaRepository;

    /**
     * @var \Riki\Rma\Model\ReviewCcFilter
     */
    protected $reviewCcFilter;

    /**
     * @var \Riki\Rma\Model\ResourceModel\RequestedMassActionFactory
     */
    protected $massActionResourceModelFactory;

    /**
     * @var \Riki\Rma\Model\Config\Source\Rma\MassAction
     */
    protected $massActionOptions;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Rma\Model\ResourceModel\Rma\CollectionFactory
     */
    protected $rmaCollectionFactory;

    /**
     * @var Payment\CollectionFactory
     */
    protected $orderPaymentCollectionFactory;

    /**
     * @var ResourceModel\Rma
     */
    protected $rmaResourceModel;

    /**
     * @var array
     */
    protected $rmaIds = [];

    /**
     * @var null
     */
    protected $action = null;

    /**
     * @var array
     */
    protected $validRmaIds = [];

    /**
     * array of [error_type => list rma id]
     *
     * @var array
     */
    protected $errors = [];

    /**
     * @var array
     */
    protected $errorMessages = [];

    /**
     * @var array
     */
    protected $allowedActions = [
        MassActionOption::CLOSE_REQUEST,
        MassActionOption::REVIEW_BY_CC,
        MassActionOption::APPROVE_BY_CC,
        MassActionOption::APPROVE_BY_CS,
        MassActionOption::DENY_REQUEST,
        MassActionOption::REJECT,
        MassActionOption::REJECT_REQUEST
    ];

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $serializer;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory
     */
    protected $orderShipmentCollectionFactory;

    /**
     * @var \Riki\Rma\Helper\Amount
     */
    protected $rikiAmountHelper;

    /**
     * MassActionValidator constructor.
     * @param Status $statusHelper
     * @param \Magento\Rma\Api\RmaRepositoryInterface $rmaRepository
     * @param ReviewCcFilter $reviewCcFilter
     * @param MassActionOption $massActionOptions
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param OrderRepositoryInterface $orderRepository
     * @param ResourceModel\Rma $rmaResourceModel
     * @param \Magento\Rma\Model\ResourceModel\Rma\CollectionFactory $rmaCollectionFactory
     * @param Payment\CollectionFactory $orderPaymentCollectionFactory
     * @param ResourceModel\RequestedMassActionFactory $massActionResourceModelFactory
     * @param \Magento\Framework\Serialize\Serializer\Json $serializer
     * @param \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $orderShipmentCollectionFactory
     * @param \Riki\Rma\Helper\Amount $rikiAmountHelper
     */
    public function __construct(
        \Riki\Rma\Helper\Status $statusHelper,
        \Magento\Rma\Api\RmaRepositoryInterface $rmaRepository,
        \Riki\Rma\Model\ReviewCcFilter $reviewCcFilter,
        \Riki\Rma\Model\Config\Source\Rma\MassAction $massActionOptions,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Riki\Rma\Model\ResourceModel\Rma $rmaResourceModel,
        \Magento\Rma\Model\ResourceModel\Rma\CollectionFactory $rmaCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory $orderPaymentCollectionFactory,
        \Riki\Rma\Model\ResourceModel\RequestedMassActionFactory $massActionResourceModelFactory,
        \Magento\Framework\Serialize\Serializer\Json $serializer,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $orderShipmentCollectionFactory,
        \Riki\Rma\Helper\Amount $rikiAmountHelper
    ) {
        $this->statusHelper = $statusHelper;
        $this->rmaRepository = $rmaRepository;
        $this->reviewCcFilter = $reviewCcFilter;
        $this->massActionOptions = $massActionOptions;
        $this->massActionResourceModelFactory = $massActionResourceModelFactory;
        $this->scopeConfig = $scopeConfig;
        $this->orderRepository = $orderRepository;
        $this->rmaCollectionFactory = $rmaCollectionFactory;
        $this->orderPaymentCollectionFactory = $orderPaymentCollectionFactory;
        $this->rmaResourceModel = $rmaResourceModel;
        $this->serializer = $serializer;
        $this->orderShipmentCollectionFactory = $orderShipmentCollectionFactory;
        $this->rikiAmountHelper = $rikiAmountHelper;
    }

    /**
     * @param array $rmaIds
     * @param $action
     * @return $this
     */
    public function initData(array $rmaIds, $action)
    {
        $this->rmaIds = $rmaIds;
        $this->action = $action;
        $this->validRmaIds = $rmaIds;

        return $this;
    }

    /**
     * @return \Riki\Rma\Model\ResourceModel\RequestedMassActionFactory
     */
    public function getMassActionResourceModelFactory()
    {
        return $this->massActionResourceModelFactory;
    }

    /**
     * @return array
     */
    public function getAllowedActions()
    {
        return $this->allowedActions;
    }

    /**
     * @return $this
     * @throws LocalizedException
     */
    public function execute()
    {
        if (!$this->action || empty($this->rmaIds)) {
            throw new LocalizedException(__('Request data is invalid.'));
        }

        $this->validateDuplicate()
            ->validateReturnStatus()
            ->validateByCondition();

        $specificConditionAction = [
            MassActionOption::REVIEW_BY_CC,
            MassActionOption::APPROVE_BY_CC,
            MassActionOption::APPROVE_BY_CS,
            MassActionOption::REJECT,
        ];
        if(in_array($this->action, $specificConditionAction)){
            $this->validateBySpecificCondition();
        }
        return $this;
    }

    /**
     * @return $this
     * @throws LocalizedException
     */
    protected function validateDuplicate()
    {
        /** @var \Riki\Rma\Model\ResourceModel\RequestedMassAction $massActionResourceModel */
        $massActionResourceModel = $this->massActionResourceModelFactory->create();

        $existedMassAction = $massActionResourceModel->getMassActionRmaIdByActionAndRmaIds(
            $this->action,
            $this->rmaIds
        );

        if (!empty($existedMassAction)) {
            $this->validRmaIds = array_diff($this->validRmaIds, $existedMassAction);
            $this->errors['duplicate'] = $existedMassAction;
            $this->errorMessages['duplicate'] = __(
                'Item(s) already exist in the waiting for %1 item list',
                $this->massActionOptions->getLabel($this->action)
            );
        }

        return $this;
    }

    /**
     * @return $this
     * @throws LocalizedException
     */
    protected function validateReturnStatus()
    {
        switch ($this->action) {
            case MassActionOption::REVIEW_BY_CC:
                $targetStatus = ReturnStatusInterface::REVIEWED_BY_CC;
                break;
            case MassActionOption::CLOSE_REQUEST:
                $targetStatus = ReturnStatusInterface::CLOSED;
                break;
            case MassActionOption::REJECT_REQUEST:
                $targetStatus = ReturnStatusInterface::CC_FEEDBACK_REJECTED;
                break;
            case MassActionOption::APPROVE_BY_CC:
                $targetStatus = ReturnStatusInterface::APPROVED_BY_CC;
                break;
            case MassActionOption::APPROVE_BY_CS:
                $targetStatus = ReturnStatusInterface::COMPLETED;
                break;
            case MassActionOption::DENY_REQUEST:
                $targetStatus = ReturnStatusInterface::REJECTED_BY_CC;
                break;
            case MassActionOption::REJECT:
                $targetStatus = ReturnStatusInterface::CS_FEEDBACK_REJECTED;
                break;
            default:
                $this->validRmaIds = [];
                throw new LocalizedException(__('Request data is invalid'));
        }

        $allowedStatuses = $this->statusHelper->getAllowedMassAction($targetStatus);

        /** @var \Magento\Rma\Model\ResourceModel\Rma\Collection $rmaCollection */
        $rmaCollection = $this->rmaCollectionFactory->create();

        $invalidRmaIds = $rmaCollection->addFieldToFilter('entity_id', ['in' => $this->validRmaIds])
            ->addFieldToFilter('return_status', ['nin' => $allowedStatuses])
            ->getAllIds();

        if (!empty($invalidRmaIds)) {
            $this->validRmaIds = array_diff($this->validRmaIds, $invalidRmaIds);

            $this->errors['return_status'] = $invalidRmaIds;
            $this->errorMessages['return_status'] = __(
                'Return status must be %1 before %2',
                $this->statusHelper->getLabel($allowedStatuses),
                $this->massActionOptions->getLabel($this->action)
            );
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function validateByCondition()
    {
        $conditions = $this->getConditionConfigValue();

        if (!empty($conditions)) {
            $validRmaIds = [];

            foreach ($conditions as $condition) {
                $needValidateRmaIds = array_diff($this->validRmaIds, $validRmaIds);

                foreach ($condition as $field => $allowedValues) {
                    if (empty($needValidateRmaIds)) {
                        continue 2;
                    }
                    $actionValidateFunc = 'getItemsValidCondition' . str_replace('_', '', ucwords($field, '_'));

                    if (method_exists($this, $actionValidateFunc)) {
                        $needValidateRmaIds = call_user_func(
                            [$this, $actionValidateFunc],
                            is_array($allowedValues)? $allowedValues : [$allowedValues],
                            $needValidateRmaIds
                        );
                    }
                }

                $validRmaIds += $needValidateRmaIds;
            }

            $invalidRmaIds = array_diff($this->validRmaIds, $validRmaIds);
            $this->validRmaIds = $validRmaIds;

            if (!empty($invalidRmaIds)) {
                $this->errors['condition'] = $invalidRmaIds;
                $this->errorMessages['condition'] = __('Item data is not match the mass action rule');
            }
        }

        return $this;
    }

    /**
     * @param $allowedFullPartial
     * @param array $rmaIds
     * @return array
     */
    protected function getItemsValidConditionFullPartial($allowedFullPartial, array $rmaIds)
    {
        $allowedFullPartialIds = array_map(function ($fop) {
            if ($fop == 'full') {
                return 1;
            }

            return 0;
        }, $allowedFullPartial);

        /** @var \Magento\Rma\Model\ResourceModel\Rma\Collection $rmaCollection */
        $rmaCollection = $this->rmaCollectionFactory->create();

        return $rmaCollection->addFieldToFilter('full_partial', ['in' => $allowedFullPartialIds])
            ->addFieldToFilter('entity_id', ['in' => $rmaIds])
            ->getAllIds();
    }

    /**
     * @param array $allowedPaymentMethods
     * @param array $rmaIds
     * @return array
     */
    protected function getItemsValidConditionPaymentMethod($allowedPaymentMethods, array $rmaIds)
    {
        /** @var \Magento\Rma\Model\ResourceModel\Rma\Collection $rmaCollection */
        $rmaCollection = $this->rmaCollectionFactory->create();

        $rmas = $rmaCollection->addFieldToSelect(['entity_id', 'order_id', 'rma_shipment_number'])
            ->addFieldToFilter('entity_id', ['in' => $rmaIds])
            ->getItems();

        $rmaIdToOrderId = array_map(function ($rma) {
            return $rma->getOrderId();
        }, $rmas);

        // Add filter condition with payment_method is npatobarai
        $validOrderIdsNpatobarai = [];
        // Review CC, Approve CC, Approve CS does not need to check for this condition
        if(!in_array($this->action, [MassActionOption::REVIEW_BY_CC, MassActionOption::APPROVE_BY_CS, MassActionOption::APPROVE_BY_CC])) {
            if (($key = array_search(NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE, $allowedPaymentMethods)) !== false) {
                unset($allowedPaymentMethods[$key]);
                $validOrderIdsNpatobarai = $this->filterConditionForPaymentNpAtobarai($rmas);
            }
        }
        /** @var \Magento\Sales\Model\ResourceModel\Order\Payment\Collection $orderPaymentCollection */
        $orderPaymentCollection = $this->orderPaymentCollectionFactory->create();
        $orderPayments = $orderPaymentCollection->addFieldToSelect('parent_id')
            ->addFieldToFilter('parent_id', array_values($rmaIdToOrderId))
            ->addFieldToFilter('method', ['in' => $allowedPaymentMethods])
            ->getItems();

        $validOrderIds = array_map(function ($orderPayment) {
            return $orderPayment->getParentId();
        }, $orderPayments);

        return array_keys(array_intersect($rmaIdToOrderId, array_merge($validOrderIds, $validOrderIdsNpatobarai)));
    }

    /**
     * @param $allowedReasons
     * @param array $rmaIds
     * @return array
     */
    protected function getItemsValidConditionReason($allowedReasons, array $rmaIds)
    {
        $allowAllReasonAction = [MassActionOption::APPROVE_BY_CC, MassActionOption::APPROVE_BY_CS];
        if(in_array($this->action, $allowAllReasonAction)){
            return $rmaIds;
        }
        /** @var \Magento\Rma\Model\ResourceModel\Rma\Collection $rmaCollection */
        $rmaCollection = $this->rmaCollectionFactory->create();

        return $rmaCollection->addFieldToFilter('reason_id', ['in' => $allowedReasons])
            ->addFieldToFilter('entity_id', ['in' => $rmaIds])
            ->getAllIds();
    }

    /**
     * @return array|mixed
     */
    protected function getConditionConfigValue()
    {
        $specificConditionAction = [
            MassActionOption::REVIEW_BY_CC,
            MassActionOption::APPROVE_BY_CC,
            MassActionOption::APPROVE_BY_CS
        ];
        if (in_array($this->action, $specificConditionAction)){
            $configValue = $this->scopeConfig->getValue('rma/mass_action/approve_condition');
        } else {
            $configValue = $this->scopeConfig->getValue('rma/mass_action/approve_condition_reject');
        }

        $configValue = $this->serializer->unserialize($configValue);
        if (is_array($configValue)) {
            return $configValue;
        }

        return [];
    }

    /**
     * @param Rma $rma
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    protected function getOrder(\Riki\Rma\Model\Rma $rma)
    {
        return $this->orderRepository->get($rma->getOrderId());
    }

    /**
     * @return array
     */
    public function getErrorMessages()
    {
        $result = [];

        foreach ($this->errors as $errorType => $errorItemIds) {
            $result[] = $this->errorMessages[$errorType]
                . ': '
                . $this->rmaResourceModel->getConcatRmaName($errorItemIds);
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getValidIds()
    {
        return $this->validRmaIds;
    }

    /**
     * Add filter condition with payment_method is npatobarai
     *
     * @param array $rmas
     * @return array
     */
    public function filterConditionForPaymentNpAtobarai($rmas)
    {
        $validOrderIds = [];
        // Add filter condition with payment_method is npatobarai
        $rmaShipmentIncrementId = [];
        /** @var \Riki\Rma\Model\Rma $rma */
        foreach ($rmas as $rma) {
            if ($rma->getData('rma_shipment_number')) {
                $rmaShipmentIncrementId[$rma->getId()] = $rma->getData('rma_shipment_number');
            }
        }

        if ($rmaShipmentIncrementId) {
            /** @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Collection $orderShipmentCollection */
            $orderShipmentCollection = $this->orderShipmentCollectionFactory->create();
            $orderShipmentCollection->addFieldToSelect('order_id')
                ->addFieldToFilter('increment_id', ['in' => array_values($rmaShipmentIncrementId)])
                ->getSelect()
                ->join(
                    ['sop' => 'sales_order_payment'],
                    'main_table.order_id = sop.parent_id AND sop.method = "' .
                    NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE . '"',
                    []
                )->join(
                    ['nat' => 'riki_np_atobarai_transaction'],
                    'main_table.entity_id = nat.shipment_id 
                    AND (nat.np_customer_payment_status IS NULL OR nat.np_customer_payment_status = ' .
                    TransactionPaymentStatus::NOT_PAID_YET_STATUS_VALUE . ')',
                    []
                );
            $orderShipments = $orderShipmentCollection->getItems();

            $validOrderIds = array_map(function ($orderShipment) {
                return $orderShipment->getOrderId();
            }, $orderShipments);
        }

        return $validOrderIds;
    }

    /**
     * Add specific filter condition for some mass action
     * @return $this
     */
    public function validateBySpecificCondition(){
        $validRmaIds = [];
        foreach ($this->validRmaIds as $rmaId) {
            $flagValue = 0; // increase when condition is true
            $flagCount = 0; // increase all condition
            $rma = $this->rmaCollectionFactory->create()
                ->addFieldToFilter('entity_id', ['equals' => $rmaId])
                ->getFirstItem();
            $order = $this->getOrder($rma);
            if(in_array($this->action, [MassActionOption::APPROVE_BY_CC, MassActionOption::APPROVE_BY_CS, MassActionOption::REJECT])){
                // Is NOT Return amount > Order amount && Is NOT Point Balance < Points to cancel: Earned points
                $totalReturnAmount = $rma->getData('total_return_amount_adjusted');
                $orderAmount = $order->getGrandTotal();
                $customerPointBalance = $this->rikiAmountHelper->getPointsBalance($rma);
                $earnedPoints = $rma->getData('total_cancel_point_adjusted');
                if ($totalReturnAmount <= $orderAmount && $customerPointBalance >= $earnedPoints){
                    ++$flagValue;
                }
                ++$flagCount;
            } else if (in_array($this->action, [MassActionOption::REVIEW_BY_CC, MassActionOption::REJECT])){
                // Order is not free of charge
                if ((int)$order->getData('free_of_charge') == 0 && $order->getData('replacement_reason') == null) {
                    ++$flagValue;
                }
                ++$flagCount;
            }

            //check payment npatobarai
            if (in_array($this->action, [MassActionOption::APPROVE_BY_CC, MassActionOption::REVIEW_BY_CC, MassActionOption::REJECT])
                && $order->getPayment()->getMethod() == NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE
            ) {
                $validOrderIdsNpatobarai = $this->filterConditionForPaymentNpAtobarai([$rma]);
                if (!empty($validOrderIdsNpatobarai)) {
                    ++$flagValue;
                }
                ++$flagCount;
            }

            if ($flagValue == $flagCount) {
                $validRmaIds[] = $rmaId;
            }
        }
        $invalidRmaIds = array_diff($this->validRmaIds, $validRmaIds);
        $this->validRmaIds = $validRmaIds;

        if (!empty($invalidRmaIds)) {
            try {
                $error = array_merge($this->errors['condition'], $invalidRmaIds);
                $this->errors['condition'] = $error;
            } catch (\Exception $e){
                $this->errors['condition'] = $invalidRmaIds;
                $this->errorMessages['condition'] = __('Item data is not match the mass action rule');
            }
        }
        return $this;
    }
}
