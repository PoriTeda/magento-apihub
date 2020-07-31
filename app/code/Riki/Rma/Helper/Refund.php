<?php
namespace Riki\Rma\Helper;

use Riki\Rma\Api\Data\Rma\RefundStatusInterface;
use Bluecom\Paygent\Model\Paygent;
use Riki\Rma\Api\ConfigInterface;
use Riki\Rma\Logger\Refund\Logger;

class Refund extends \Magento\Framework\App\Helper\AbstractHelper
{
    const SKIP_VALIDATE_REFUND_AMOUNT_KEY = 'skip_validate_refund_amount';

    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $paymentDataHelper;

    /**
     * @var \Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader
     */
    protected $creditMemoLoader;

    /**
     * @var \Magento\Sales\Api\CreditmemoManagementInterface
     */
    protected $creditMemoManagement;

    /**
     * @var \Riki\Rma\Api\ItemRepositoryInterface
     */
    protected $rmaItemRepository;

    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $searchHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Sales\Model\Order\Creditmemo
     */
    protected $creditMemo;

    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * @var \Riki\Framework\Helper\ScopeConfig
     */
    protected $scopeConfigHelper;

    /**
     * @var \Riki\Rma\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Riki\Rma\Helper\Amount
     */
    protected $amountHelper;

    /**
     * @var \Riki\Rma\Api\Rma\Status\HistoryRepositoryInterface
     */
    protected $historyRepository;

    /**
     * @var \Riki\Framework\Helper\Datetime
     */
    protected $datetimeHelper;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Refund constructor.
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param Data $dataHelper
     * @param \Riki\Rma\Helper\Amount $amountHelper
     * @param \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Magento\Sales\Model\Order\Creditmemo $creditMemo
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\Rma\Api\ItemRepositoryInterface $rmaItemRepository
     * @param \Magento\Sales\Api\CreditmemoManagementInterface $creditMemoManagement
     * @param \Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader $creditMemoLoader
     * @param \Magento\Payment\Helper\Data $paymentDataHelper
     * @param \Riki\Rma\Api\Rma\Status\HistoryRepositoryInterface $historyRepositoryInterface
     * @param \Riki\Framework\Helper\Datetime $datetime
     * @param Logger $logger
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Riki\Framework\Helper\Search $searchHelper,
        \Riki\Rma\Helper\Data $dataHelper,
        \Riki\Rma\Helper\Amount $amountHelper,
        \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper,
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Magento\Sales\Model\Order\Creditmemo $creditMemo,
        \Magento\Framework\Registry $registry,
        \Riki\Rma\Api\ItemRepositoryInterface $rmaItemRepository,
        \Magento\Sales\Api\CreditmemoManagementInterface $creditMemoManagement,
        \Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader $creditMemoLoader,
        \Magento\Payment\Helper\Data $paymentDataHelper,
        \Riki\Rma\Api\Rma\Status\HistoryRepositoryInterface $historyRepositoryInterface,
        \Riki\Framework\Helper\Datetime $datetime,
        \Riki\Rma\Logger\Refund\Logger $logger,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->searchHelper = $searchHelper;
        $this->dataHelper = $dataHelper;
        $this->amountHelper = $amountHelper;
        $this->scopeConfigHelper = $scopeConfigHelper;
        $this->functionCache = $functionCache;
        $this->creditMemo = $creditMemo;
        $this->registry = $registry;
        $this->rmaItemRepository = $rmaItemRepository;
        $this->creditMemoManagement = $creditMemoManagement;
        $this->creditMemoLoader = $creditMemoLoader;
        $this->paymentDataHelper = $paymentDataHelper;
        $this->historyRepository = $historyRepositoryInterface;
        $this->datetimeHelper = $datetime;

        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Get all payment methods
     *
     * @return mixed[]
     */
    public function getPaymentMethods()
    {
        if ($this->functionCache->has()) {
            return $this->functionCache->load();
        }

        $result = $this->paymentDataHelper->getPaymentMethods();
        $this->functionCache->store($result);

        return $result;
    }

    /**
     * Get enable payment methods for refund function
     *
     * @return mixed[]
     */
    public function getEnablePaymentMethods()
    {
        if ($this->functionCache->has()) {
            return $this->functionCache->load();
        }
        $enableMethods = $this->scopeConfigHelper->read(ConfigInterface::class)
            ->rma()
            ->refundMethod()
            ->enablePayment();
        $enableMethods = explode(',', $enableMethods);

        $methods = [];

        foreach ($this->getPaymentMethods() as $id => $method) {
            if (!in_array($id, $enableMethods)) {
                continue;
            }
            $methods[$id] = $method;
        }

        $this->functionCache->store($methods);

        return $methods;
    }

    /**
     * Get enable refund methods for refund function
     *
     * @return mixed[]
     */
    public function getEnableRefundMethods()
    {
        if ($this->functionCache->has()) {
            return $this->functionCache->load();
        }

        $enableMethods = $this->scopeConfigHelper->read(ConfigInterface::class)
            ->rma()
            ->refundMethod()
            ->enableRefund();
        $enableMethods = explode(',', $enableMethods);

        $methods = [];
        foreach ($this->getPaymentMethods() as $id => $method) {
            if (!in_array($id, $enableMethods)) {
                continue;
            }
            $methods[$id] = $method;
        }

        $this->functionCache->store($methods);

        return $methods;
    }

    /**
     * Get refund method by payment method code
     *
     * @param $method
     * @param $rma
     * @return array
     */
    public function getRefundMethodsByPaymentMethod($method, $rma = null)
    {
        $cacheKey = $method;
        if ($rma) {
            $cacheKey = $rma->getId() . $method;
        }
        if ($this->functionCache->has($cacheKey)) {
            return $this->functionCache->load($cacheKey);
        }
        $methods = [];
        if ($rma) {
            $customer = $this->dataHelper->getRmaCustomer($rma);
            if ($customer) {
                $offlineCustomerAttribute = $customer->getCustomAttribute('offline_customer');
                $isOfflineCustomer = is_object($offlineCustomerAttribute) ? $offlineCustomerAttribute->getValue() : 0;
                $defaultPath = ConfigInterface::RMA . "/{$method}/online_member_default";
                if ($isOfflineCustomer) {
                    $defaultPath = ConfigInterface::RMA . "/{$method}/offline_member_default";
                }
                $defaultMethod = $this->scopeConfig->getValue($defaultPath);
                if ($defaultMethod) {
                    $methods[$defaultMethod] = $defaultMethod;
                }
            }
        }
        $alternative = $this->scopeConfig->getValue(ConfigInterface::RMA . "/{$method}/alternative");
        if ($alternative) {
            $alternative = explode(',', $alternative);
            foreach ($alternative as $m) {
                $methods[$m] = $m;
            }
        }

        $paymentMethods = $this->getPaymentMethods();
        foreach ($methods as $id => $value) {
            if (!isset($paymentMethods[$id])) {
                unset($methods[$id]);
                continue;
            }
            $methods[$id] = $paymentMethods[$id]['title'];
        }

        $this->functionCache->store($methods, $cacheKey);

        return $methods;
    }

    /**
     * Can execute refund (trigger credit memo)
     *
     * @param \Magento\Rma\Model\Rma $rma
     * @return bool
     */
    public function canRefund(\Magento\Rma\Model\Rma $rma)
    {
        $allowed = [
            RefundStatusInterface::BT_COMPLETED,
            RefundStatusInterface::CHECK_ISSUED,
            RefundStatusInterface::APPROVED,
            RefundStatusInterface::WAITING_APPROVAL,
            RefundStatusInterface::MANUALLY_CARD_COMPLETED
        ];

        if (!in_array($rma->getData('refund_status'), $allowed)) {
            return false;
        }

        if ($rma->getData('refund_status') == RefundStatusInterface::APPROVED
            && $rma->getData('refund_method') != Paygent::CODE
        ) {
            return false;
        }

        $order = $this->dataHelper->getRmaOrder($rma);
        if (!$order || !$order->getId() || !$order->canCreditmemo()) {
            return false;
        }

        return true;
    }

    /**
     * Refund a return
     *
     * @param \Riki\Rma\Model\Rma $rma
     * @return \Magento\Sales\Api\Data\CreditmemoInterface
     */
    public function refund(\Riki\Rma\Model\Rma $rma)
    {
        if (!$this->canRefund($rma)) {
            return $this->creditMemo;
        }

        $creditMemo = $this->prepareCreditMemo($rma);

        if ($creditMemo) {
            return $this->creditMemoManagement->refund(
                $creditMemo,
                $this->isOfflineRefund($rma)
            );
        }

        return false;
    }

    /**
     * @param \Magento\Rma\Model\Rma $rma
     * @return bool
     */
    public function isOfflineRefund(\Magento\Rma\Model\Rma $rma)
    {
        if ($rma->getMustOfflineRefund()) {
            return true;
        }

        return $rma->getData('refund_method') != Paygent::CODE;
    }

    /**
     * @param \Riki\Rma\Model\Rma $rma
     * @return false|\Magento\Sales\Model\Order\Creditmemo
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function prepareCreditMemo(\Riki\Rma\Model\Rma $rma)
    {
        $this->registry->unregister('current_creditmemo');
        if ($this->registry->registry(Constant::REGISTRY_KEY_DISABLE_COLLECT_TOTAL_CREDIT_MEMO) === null) {
            $this->registry->register(Constant::REGISTRY_KEY_DISABLE_COLLECT_TOTAL_CREDIT_MEMO, true);
        }
        $data = [
            'items' => [],
            'shipping_amount' => 0,
            'adjustment_positive' => 0,
            'adjustment_negative' => 0
        ];

        $data['items'] = $this->prepareRefundItems($rma);

        $order = $this->dataHelper->getRmaOrder($rma);
        if (!$order instanceof \Magento\Sales\Model\Order) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The order %1 linked to return does not exists, please try again...'),
                $rma->getOrderIncrementId()
            );
        }
        // @todo what happen if multiple invoice
        foreach ($order->getInvoiceCollection() as $invoice) {
            if ($invoice->getData('is_used_for_refund')) {
                //continue;
            }
            $this->creditMemoLoader->setInvoiceId($invoice->getId());
            break;
        }
        $this->creditMemoLoader->setOrderId($rma->getOrderId());
        $data['rma'] = $rma;
        $this->creditMemoLoader->setCreditmemo($data);
        $creditMemo = $this->creditMemoLoader->load();

        if (!$creditMemo) {
            return false;
        }

        $creditMemo->setDiscountAmount(0);
        $creditMemo->setBaseDiscountAmount(0);
        $creditMemo->setTaxAmount(0);
        $creditMemo->setBaseTaxAmount(0);
        $creditMemo->setShippingAmount(0);
        $creditMemo->setBaseShippingAmount(0);
        $creditMemo->setShippingDiscountTaxCompensationAmount(0);
        $creditMemo->setBaseShippingDiscountTaxCompensationAmnt(0);
        $creditMemo->setShippingTaxAmount(0);
        $creditMemo->setBaseShippingTaxAmount(0);
        $creditMemo->setShippingInclTax(0);
        $creditMemo->setBaseShippingInclTax(0);
        $creditMemo->setSubtotal(0);
        $creditMemo->setBaseSubtotal(0);
        $creditMemo->setSubtotalInclTax(0);
        $creditMemo->setBaseSubtotalInclTax(0);
        $creditMemo->setGrandTotal(0);
        $creditMemo->setBaseGrandTotal(0);
        $creditMemo->setAdjustmentNegative(0);
        $creditMemo->setBaseAdjustmentNegative(0);
        $creditMemo->setAdjustmentPositive(0);
        $creditMemo->setBaseAdjustmentPositive(0);
        $creditMemo->collectTotals();

        // @what happen here: we will ignore collect total, then update value [adjustment_positive, adjustment_negative] which only affect on grand total collect
        // @goal grand_total of creditmemo will be same with total_return_amount of rma
        $amount = floatval($rma->getData('total_return_amount_adjusted')) - $creditMemo->getGrandTotal();
        if ($amount >= 0) {
            $creditMemo->setBaseAdjustmentPositive($amount);
            $creditMemo->setAdjustmentPositive($amount);
        } else {
            $creditMemo->setBaseAdjustmentNegative(abs($amount));
            $creditMemo->setAdjustmentNegative(abs($amount));
        }
        $creditMemo->setDiscountAmount(0);
        $creditMemo->setBaseDiscountAmount(0);
        $creditMemo->setTaxAmount(0);
        $creditMemo->setBaseTaxAmount(0);
        $creditMemo->setShippingAmount(0);
        $creditMemo->setBaseShippingAmount(0);
        $creditMemo->setShippingDiscountTaxCompensationAmount(0);
        $creditMemo->setBaseShippingDiscountTaxCompensationAmnt(0);
        $creditMemo->setShippingTaxAmount(0);
        $creditMemo->setBaseShippingTaxAmount(0);
        $creditMemo->setShippingInclTax(0);
        $creditMemo->setBaseShippingInclTax(0);
        $creditMemo->setSubtotal(0);
        $creditMemo->setBaseSubtotal(0);
        $creditMemo->setSubtotalInclTax(0);
        $creditMemo->setBaseSubtotalInclTax(0);
        $creditMemo->setGrandTotal(0);
        $creditMemo->setBaseGrandTotal(0);
        $creditMemo->collectTotals();
        $creditMemo->setData('return_shipping_fee_adjusted', $rma->getData('return_shipping_fee_adjusted'));
        $creditMemo->setData('return_payment_fee_adjusted', $rma->getData('return_payment_fee_adjusted'));
        $creditMemo->setData('return_point_not_retractable', $this->amountHelper->getNotRetractablePoints($rma));
        $creditMemo->setData('total_return_amount_adj', $rma->getData('total_return_amount_adj'));
        $creditMemo->setData('total_return_point_adjusted', $rma->getData('total_return_point_adjusted'));

        if ($rma->getRefundStatus() == RefundStatusInterface::MANUALLY_CARD_COMPLETED) {
            $creditMemo->setData(self::SKIP_VALIDATE_REFUND_AMOUNT_KEY, true);
        }

        return $creditMemo;
    }

    /**
     * @param \Riki\Rma\Model\Rma $rma
     * @return array
     */
    public function prepareRefundItems(\Riki\Rma\Model\Rma $rma)
    {
        $result = [];

        $items = $rma->getRmaItems();
        /** @var \Riki\Rma\Model\Item $item */
        foreach ($items as $itemId => $item) {
            $result[$item->getData('order_item_id')] = [
                'qty' => $item->getData('qty_requested')
            ];
        }

        return $result;
    }

    /**
     * Get label of refund method
     *
     * @param $method
     *
     * @return string
     */
    public function getRefundMethodLabel($method)
    {
        $methods = $this->getEnableRefundMethods();

        return isset($methods[$method]['title']) ? $methods[$method]['title'] : '';
    }

    /**
     *
     * @param $rmaId
     * @param $comment
     *
     * @return self
     */
    public function addHistoryComment($rmaId, $comment)
    {
        if ($rmaId instanceof \Magento\Rma\Model\Rma) {
            $rmaId = $rmaId->getId();
        }

        $history = $this->historyRepository->createFromArray([
            'rma_entity_id' => $rmaId,
            'comment' => $comment,
            'created_at' => $this->datetimeHelper->toDb(),
            'is_admin' => true,
        ]);

        try {
            $this->historyRepository->save($history);
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }

        return $this;
    }
}
