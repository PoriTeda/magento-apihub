<?php
namespace Riki\AdvancedInventory\Observer;

use Magento\Framework\Event\Observer;
use Riki\Subscription\Helper\Order\Data;
use Riki\Customer\Model\StatusMachine;

class OosCapture implements \Magento\Framework\Event\ObserverInterface
{
    const EVENT = 'advanced_inventory_oos_capture';

    /**
     * @var array
     */
    protected $oos = [];

    /**
     * @var \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface
     */
    protected $outOfStockRepository;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Riki\AdvancedInventory\Helper\Logger
     */
    protected $loggerHelper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Riki\Customer\Model\CustomerRepository
     */
    protected $rikiCustomerRepository;

    /**
     * @var \Riki\Subscription\Logger\LoggerFreeMachine
     */
    protected $loggerFreeMachine;

    /**
     * @var \Riki\Subscription\Helper\Order\Email
     */
    protected $emailOrderBuilder;

    /**
     * OosCapture constructor.
     *
     * @param \Riki\AdvancedInventory\Helper\Logger $loggerHelper
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface $outOfStockRepository
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Riki\Customer\Model\CustomerRepository $rikiCustomerRepository
     * @param \Riki\Subscription\Logger\LoggerFreeMachine $loggerFreeMachine
     * @param \Riki\Subscription\Helper\Order\Email $emailOrderBuilder
     */
    public function __construct(
        \Riki\AdvancedInventory\Helper\Logger $loggerHelper,
        \Magento\Framework\Registry $registry,
        \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface $outOfStockRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Riki\Customer\Model\CustomerRepository $rikiCustomerRepository,
        \Riki\Subscription\Logger\LoggerFreeMachine $loggerFreeMachine,
        \Riki\Subscription\Helper\Order\Email $emailOrderBuilder
    ) {
        $this->loggerHelper = $loggerHelper;
        $this->registry = $registry;
        $this->outOfStockRepository = $outOfStockRepository;
        $this->scopeConfig = $scopeConfig;
        $this->rikiCustomerRepository = $rikiCustomerRepository;
        $this->loggerFreeMachine = $loggerFreeMachine;
        $this->emailOrderBuilder = $emailOrderBuilder;
    }

    /**
     * @param Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->getIsEnabled($observer) ||
            !$this->canCapture($observer)
        ) {
            return;
        }

        $this->captureFreeMachine($observer);
        $this->captureFreeGift($observer);
        $this->capturePrize($observer);
        $this->captureSubscription($observer);
        $this->captureB2cMachine($observer);
    }

    /**
     * Get is enabled
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return bool
     */
    public function getIsEnabled(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        // should not run on simulate sub profile
        if ($quote instanceof \Riki\Subscription\Model\Emulator\Cart) {
            return false;
        }

        // should not run on oos generating
        if ($this->registry->registry(\Riki\AdvancedInventory\Cron\OutOfStock\GenerateOrder::FLAG_RUNNING)) {
            return false;
        }

        return true;
    }

    /**
     * Get oos by quoteId
     *
     * @param null $quoteId
     *
     * @return \Riki\AdvancedInventory\Model\OutOfStock[]
     */
    public function getOutOfStocks($quoteId = null)
    {
        return $quoteId === 'all'
            ? $this->oos
            : (isset($this->oos[$quoteId]) ? $this->oos[$quoteId] : []);
    }

    /**
     * Clean oos by quoteId
     *
     * @param null $quoteId
     */
    public function cleanOutOfStocks($quoteId = null)
    {
        if (isset($this->oos[$quoteId])) {
            unset($this->oos[$quoteId]);
            return;
        }

        $this->oos = [];
    }

    /**
     * Generate unique key from data
     *
     * @param $data
     *
     * @return string
     */
    public function generateUniqueKey($data)
    {
        return implode('|', $data);
    }

    /**
     * Capture oos free machine
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     */
    public function captureFreeMachine(\Magento\Framework\Event\Observer $observer)
    {
        $isDuoMachine = $observer->getEvent()->getIsDuoMachine();
        $isSkuSpecified = $observer->getEvent()->getIsSkuSpecified();
        /** @var \Riki\SubscriptionMachine\Model\MachineSkus $machineSku */
        $machineSku = $observer->getEvent()->getMachineSku();
        if (!$machineSku instanceof \Riki\SubscriptionMachine\Model\MachineSkus) {
            if (!$isSkuSpecified) {
                return;
            }
        }

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getQuote();

        // Allow continue to generate order has oos free machine if this is duo machine
        if ($quote->getProfileId() && !$isDuoMachine) { // generate after profile
            // Update machine rental status when all of machines is out of stock for case generate order
            $machineData = $observer->getEvent()->getMachineData();
            if ($machineData) {
                $this->updateMachineRentalStatus($machineData, $quote->getProfileId());
            }
            throw new \Magento\Framework\Exception\LocalizedException(__('Free attachment machine was out of stock'));
        }

        $product = $observer->getEvent()->getProduct();
        if (!$product instanceof \Magento\Catalog\Model\Product
            || ($product->getTypeId() == \Magento\Bundle\Model\Product\Type::TYPE_CODE)
        ) {
            return;
        }

        $data = [
            'quote_id' => $quote->getId(),
            'product_id' => $product->getId(),
            'product_sku' => $product->getSku(),
            'qty' => 1, // machine only attach one
            'customer_id' => $quote->getCustomerId(),
            'store_id' => $quote->getStoreId(),
            'machine_sku_id' => ($machineSku) ? $machineSku->getId() : null,
            'subscription_profile_id' => ($quote->getProfileId() ?: null),
            'is_duo_machine' => $isDuoMachine,
            'is_sku_specified' => $isSkuSpecified
        ];

        $key = $this->generateUniqueKey($data);
        if (isset($this->oos[$data['quote_id']][$key])) { // prevent add duplicate item
            return;
        }

        $data['uniq_key'] = $key;
        $data['quote'] = $quote;
        $data['product'] = $product;
        $data['machine_sku'] = $machineSku;

        /** @var \Riki\AdvancedInventory\Model\OutOfStock $outOfStock */
        $outOfStock = $this->outOfStockRepository->createFromArray($data);
        if (!$outOfStock->initNewQuoteItem($quote)) {
            return;
        }

        $this->oos[$data['quote_id']][$key] = $outOfStock;
    }

    /**
     * Capture oos free gift
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     */
    public function captureFreeGift(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getQuote();

        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getEvent()->getProduct();

        $qty = intval($observer->getEvent()->getQty());
        if (!$qty) {
            return;
        }

        $salesruleId = intval($observer->getEvent()->getSalesruleId());
        if (!$salesruleId) {
            return;
        }

        $data = [
            'quote_id' => $quote->getId(),
            'product_id' => $product->getId(),
            'product_sku' => $product->getSku(),
            'qty' => $qty,
            'salesrule_id' => $salesruleId,
            'customer_id' => $quote->getCustomerId(),
            'store_id' => $quote->getStoreId(),
            'subscription_profile_id' => ($quote->getProfileId() ?: null)
        ];

        $key = $this->generateUniqueKey($data);
        if (isset($this->oos[$data['quote_id']][$key])) { // prevent add duplicate item
            return;
        }

        $data['uniq_key'] = $key;
        $data['quote'] = $quote;
        $data['product'] = $product;

        /** @var \Riki\AdvancedInventory\Model\OutOfStock $outOfStock */
        $outOfStock = $this->outOfStockRepository->createFromArray($data);
        if (!$outOfStock->initNewQuoteItem($quote)) {
            return;
        }

        $this->oos[$data['quote_id']][$key] = $outOfStock;
    }

    /**
     * Capture oos on subscription
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function captureSubscription(\Magento\Framework\Event\Observer $observer)
    {
        // only capture normal product on subscription
        /** @var \Riki\SubscriptionMachine\Model\MachineSkus $machineSku */
        $machineSku = $observer->getEvent()->getMachineSku();
        if ($machineSku instanceof \Riki\SubscriptionMachine\Model\MachineSkus) {
            return;
        }

        $isSkuSpecified = $observer->getEvent()->getIsSkuSpecified();
        if ($isSkuSpecified) {
            return;
        }

        $salesruleId = intval($observer->getEvent()->getSalesruleId());
        if ($salesruleId) {
            return;
        }
        /** @var \Riki\Prize\Model\Prize $prize */
        $prize = $observer->getEvent()->getPrize();
        if ($prize instanceof \Riki\Prize\Model\Prize) {
            return;
        }

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getQuote();

        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getEvent()->getProduct();

        $qty = intval($observer->getEvent()->getQty());
        if (!$qty) {
            return;
        }

        $profileId = intval($quote->getProfileId());
        if (!$profileId) {
            return;
        }

        if (!$quote->getId()) {
            try {
                $quote->save();
            } catch (\Exception $e) {
                $this->loggerHelper->getOosLogger()->critical($e);
                throw $e;
            }
        }

        $isDelayPayment = $observer->getEvent()->getData('is_delay_payment');
        $subscriptionType = $observer->getEvent()->getData('subscription_type');
        $data = [
            'quote_id' => $quote->getId(),
            'product_id' => $product->getId(),
            'product_sku' => $product->getSku(),
            'qty' => $qty,
            'unit_qty' => $observer->getEvent()->getUnitQty(),
            'unit_case' => $observer->getEvent()->getUnitCase(),
            'customer_id' => $quote->getCustomerId(),
            'store_id' => $quote->getStoreId(),
            'subscription_profile_id' => $profileId,
            'original_delivery_date'    => $observer->getEvent()->getOriginalDeliveryDate(),
            'gw_id' => $observer->getEvent()->getGwId(),
            'additional_data' => json_encode([
                'is_delay_payment' => $isDelayPayment,
                'subscription_type' => $subscriptionType
                ])
        ];
        $key = $this->generateUniqueKey($data);
        if (isset($this->oos[$data['quote_id']][$key])) { // prevent add duplicate item
            return;
        }

        $data['uniq_key'] = $key;
        $data['quote'] = $quote;
        $data['product'] = $product;

        /** @var \Riki\AdvancedInventory\Model\OutOfStock $outOfStock */
        $outOfStock = $this->outOfStockRepository->createFromArray($data);
        if (!$outOfStock->initNewQuoteItem($quote)) {
            return;
        }

        $this->oos[$data['quote_id']][$key] = $outOfStock;
    }

    /**
     * Capture oos prize
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function capturePrize(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getQuote();

        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getEvent()->getProduct();

        /** @var \Riki\Prize\Model\Prize $prize */
        $prize = $observer->getEvent()->getPrize();
        if (!$prize instanceof \Riki\Prize\Model\Prize) {
            return;
        }

        $data = [
            'quote_id' => $quote->getId(),
            'product_id' => $product->getId(),
            'product_sku' => $product->getSku(),
            'qty' => $this->getProductQuantityOrdered($product, $prize->getQty()),
            'customer_id' => $quote->getCustomerId(),
            'store_id' => $quote->getStoreId(),
            'prize_id' => $prize->getId(),
            'subscription_profile_id' => ($quote->getProfileId() ?: null)
        ];

        $key = $this->generateUniqueKey($data);
        if (isset($this->oos[$data['quote_id']][$key])) { // prevent add duplicate item
            return;
        }

        $data['uniq_key'] = $key;
        $data['quote'] = $quote;
        $data['product'] = $product;
        $data['prize'] = $prize;

        /** @var \Riki\AdvancedInventory\Model\OutOfStock $outOfStock */
        $outOfStock = $this->outOfStockRepository->createFromArray($data);
        if (!$outOfStock->initNewQuoteItem($quote)) {
            return;
        }

        $this->oos[$data['quote_id']][$key] = $outOfStock;
    }

    /**
     * @param Observer $observer
     */
    public function captureB2cMachine(Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getQuote();

        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        $quoteItem = $observer->getEvent()->getQuoteItem();

        if ($machineTypeId = $observer->getEvent()->getMachineTypeId()) {
            $data = [
                'quote_id' => $quote->getId(),
                'product_id' => $quoteItem->getProductId(),
                'product_sku' => $quoteItem->getSku(),
                'qty' => $quoteItem->getQty(),
                'customer_id' => $quote->getCustomerId(),
                'store_id' => $quote->getStoreId(),
                'machine_type_id'   =>  $machineTypeId,
                'subscription_profile_id' => null
            ];

            $key = $this->generateUniqueKey($data);
            if (isset($this->oos[$data['quote_id']][$key])) { // prevent add duplicate item
                return;
            }

            $data['uniq_key'] = $key;
            $data['quote'] = $quote;
            $data['product'] = $quoteItem->getProduct();
            $data['additional_data'] = ['machine_type_id' => $machineTypeId];

            /** @var \Riki\AdvancedInventory\Model\OutOfStock $outOfStock */
            $outOfStock = $this->outOfStockRepository->createFromArray($data);
            if (!$outOfStock->initNewQuoteItem($quote)) {
                return;
            }

            $this->oos[$data['quote_id']][$key] = $outOfStock;
        }
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return bool
     */
    public function canCapture(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getQuote();
        if (!$quote instanceof \Magento\Quote\Model\Quote) {
            return false;
        }

        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getEvent()->getProduct();
        if (!$product instanceof \Magento\Catalog\Model\Product) {
            return false;
        }

        return true;
    }

    /**
     * get product quantity ordered
     *
     * @param $product
     * @param $qty
     * @return mixed
     */
    protected function getProductQuantityOrdered(
        \Magento\Catalog\Model\Product $product,
        $qty
    ) {
        $productUnitQty = 1;

        if ($product->getCaseDisplay() == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::CD_CASE_ONLY) {
            $productUnitQty = (int)$product->getUnitQty() ? (int)$product->getUnitQty() : 1;
        }

        return (int) $qty * $productUnitQty;
    }

    /**
     * Update machine rental status when all of machines is out of stock for case generate order
     *
     * @param array $machineData
     * @param int $profileId
     */
    private function updateMachineRentalStatus($machineData, $profileId)
    {
        if (empty($machineData)) {
            return;
        }

        if (isset($machineData['status']) && $machineData['status'] == StatusMachine::MACHINE_STATUS_VALUE_OOS) {
            $this->updateOosStatusToConsumerDB($profileId, $machineData);

            // Send email notify to admin
            if ($this->scopeConfig->getValue(Data::CONFIG_FREE_MACHINE_EMAIL_ENABLE)) {
                $this->sendEmailNotifyToAdmin(
                    $machineData['consumer_db_id'],
                    $machineData['variables']
                );
            }
        }
    }

    /**
     * Update OOS status to ConsumerDB for machine rental status to “11. Machine attachment error (OOS)”
     *
     * @param int $profileId
     * @param array $machineData
     */
    private function updateOosStatusToConsumerDB($profileId, $machineData)
    {
        $machineCustomer = $machineData['machine'];
        $statusId = $machineData['status'];
        $consumerDbId = $machineData['consumer_db_id'];
        $machineTypeCode = $machineCustomer->getData('machine_type_code');
        $arrSubProfileId = $this->rikiCustomerRepository->getSubProfileIdByMachineTypeOptionArray();
        $machineTypeSubProfileId = $arrSubProfileId[$machineTypeCode];
        $dataToUpdate = [
            $machineTypeSubProfileId => $statusId
        ];

        $responseSubCustomer = $this->rikiCustomerRepository->setCustomerSubAPI($consumerDbId, $dataToUpdate);
        if ($responseSubCustomer) {
            $machineCustomer->setData('status', $statusId);
            try {
                $machineCustomer->save();
                $this->loggerFreeMachine->addInfo(
                    'ProfileID ' . $profileId . '::Customer #' . $consumerDbId . '::' . $machineTypeCode .
                    '::Status changed to ' . $statusId
                );
            } catch (\Exception $e) {
                $this->loggerFreeMachine->addError(
                    'ProfileID '.$profileId.'::Customer #' . $consumerDbId . '::' . $machineTypeCode .
                    ' failed to update status to ' . $statusId
                );
                $this->loggerFreeMachine->addCritical($e);
            }
        }
    }

    /**
     * Send email notify to admin
     *
     * @param string $consumerDbId
     * @param array $variable
     */
    private function sendEmailNotifyToAdmin($consumerDbId, $variable)
    {
        $emailTemplateVariables = [
            'consumer_db_id' => $consumerDbId,
            'machine_type_code' => $variable['machine_type_code'],
            'sku' => isset($variable['sku_oos']) ? $variable['sku_oos'] : null
        ];

        $emailTemplate = $this->scopeConfig->getValue(Data::CONFIG_FREE_MACHINE_EMAIL_TEMPLATE_OOS);
        $from = $this->scopeConfig->getValue(Data::CONFIG_FREE_MACHINE_EMAIL_SENDER);
        $to = $this->scopeConfig->getValue(Data::CONFIG_FREE_MACHINE_EMAIL_RECEIVER);

        if ($emailTemplate && $to) {
            $this->emailOrderBuilder->sendEmailNotification(
                $emailTemplateVariables,
                $emailTemplate,
                \Magento\Framework\App\Area::AREA_ADMINHTML,
                $from,
                $to
            );
        }
    }
}
