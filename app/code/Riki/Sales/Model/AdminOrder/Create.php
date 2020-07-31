<?php

namespace Riki\Sales\Model\AdminOrder;
use Riki\Sales\Model\Config\Source\OrderType as OrderChargeType;

class Create extends \Magento\Sales\Model\AdminOrder\Create
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;
    /**
     * @var \Wyomind\AdvancedInventory\Model\Stock
     */
    protected $stock;

    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $courseFactory;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Sales\Model\Config $salesConfig,
        \Magento\Backend\Model\Session\Quote $quoteSession,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\DataObject\Copy $objectCopyService,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Sales\Model\AdminOrder\Product\Quote\Initializer $quoteInitializer,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Customer\Api\Data\AddressInterfaceFactory $addressFactory,
        \Magento\Customer\Model\Metadata\FormFactory $metadataFormFactory,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\AdminOrder\EmailSender $emailSender,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Quote\Model\Quote\Item\Updater $quoteItemUpdater,
        \Magento\Framework\DataObject\Factory $objectFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Customer\Api\AccountManagementInterface $accountManagement,
        \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerFactory,
        \Magento\Customer\Model\Customer\Mapper $customerMapper,
        \Magento\Quote\Api\CartManagementInterface $quoteManagement,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\Sales\Api\OrderManagementInterface $orderManagement,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Framework\App\RequestInterface $request,
        \Wyomind\AdvancedInventory\Model\Stock $stock,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        array $data = []
    ) {
        $this->request = $request;
        $this->stock = $stock;
        $this->courseFactory = $courseFactory;
        parent::__construct(
            $objectManager,
            $eventManager,
            $coreRegistry,
            $salesConfig,
            $quoteSession,
            $logger,
            $objectCopyService,
            $messageManager,
            $quoteInitializer,
            $customerRepository,
            $addressRepository,
            $addressFactory,
            $metadataFormFactory,
            $groupRepository,
            $scopeConfig,
            $emailSender,
            $stockRegistry,
            $quoteItemUpdater,
            $objectFactory,
            $quoteRepository,
            $accountManagement,
            $customerFactory,
            $customerMapper,
            $quoteManagement,
            $dataObjectHelper,
            $orderManagement,
            $quoteFactory,
            $data
        );
    }

    /**
     * Override create new order, add event checkout_submit_before
     *
     * @return \Magento\Sales\Model\Order
     */
    public function createOrder()
    {
        $this->_prepareCustomer();
        $this->_validate();
        $quote = $this->getQuote();
        $this->_prepareQuoteItems();

        $orderData = [];
        if ($this->getSession()->getOrder()->getId()) {
            $oldOrder = $this->getSession()->getOrder();
            $orderData = [
                'relation_parent_id' => $oldOrder->getId(),
                'relation_parent_real_id' => $oldOrder->getIncrementId(),
                'edit_increment' => $oldOrder->getEditIncrement() + 1
            ];
        }

        $this->_eventManager->dispatch('checkout_submit_before', ['quote' => $quote]);

        $order = $this->quoteManagement->submit($quote, $orderData);

        if ($this->getSession()->getOrder()->getId()) {
            $oldOrder = $this->getSession()->getOrder();
            $oldOrder->setRelationChildId($order->getId());
            $oldOrder->setRelationChildRealId($order->getIncrementId());
            $oldOrder->setIsCancelByEditAction(true);
            $oldOrder->cancel();
            $oldOrder->addStatusHistoryComment(__('Cancelled and replaced by %1', $order->getIncrementId()));
            $oldOrder->save();
        }
        if ($this->getSendConfirmation()) {
            try {
                $this->emailSender->send($order);
            } catch (\Exception $e) {
                $this->_logger->critical($e);
                $this->messageManager->addWarning(
                    __('You did not email your customer. Please check your email settings.')
                );
            }
        }

        $this->_eventManager->dispatch('checkout_submit_all_after', ['order' => $order, 'quote' => $quote]);

        return $order;
    }

    /**
     * Prepare options array for info buy request
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return array
     */
    protected function _prepareOptionsForRequest($item)
    {
        $newInfoOptions = parent::_prepareOptionsForRequest($item);

        $productOptions = $item->getProduct()->getTypeInstance()->getOrderOptions($item->getProduct());

        if (isset($productOptions['info_buyRequest']['options']['ampromo_rule_id'])
            && !isset($newInfoOptions['ampromo_rule_id'])
        ) {
            $newInfoOptions['ampromo_rule_id'] = $productOptions['info_buyRequest']['options']['ampromo_rule_id'];
        }

        return $newInfoOptions;
    }

    /**
     * Add account data to quote
     *
     * @param array $accountData
     * @return $this
     */
    public function setAccountData($accountData)
    {
        $customer = $this->getQuote()->getCustomer();
        $form = $this->_createCustomerForm($customer);

        // emulate request
        $request = $form->prepareRequest($accountData);
        $data = $form->extractData($request);

        $attributesValue = $this->customerMapper->toFlatArray($customer);

        // custom
        foreach ($data as $attributeCode => $value) {
            if (!isset($accountData[$attributeCode]) &&
                isset($attributesValue[$attributeCode])
            ) {
                $data[$attributeCode] = $attributesValue[$attributeCode];
            }
        }
        //

        $data = $form->restoreData($data);

        $customer = $this->customerFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $customer,
            $data,
            \Magento\Customer\Api\Data\CustomerInterface::class
        );
        $this->getQuote()->updateCustomerData($customer);
        $data = [];

        $customerData = $this->customerMapper->toFlatArray($customer);
        foreach ($form->getAttributes() as $attribute) {
            $code = sprintf('customer_%s', $attribute->getAttributeCode());
            $data[$code] = isset($customerData[$attribute->getAttributeCode()])
                ? $customerData[$attribute->getAttributeCode()]
                : null;
        }

        if (isset($data['customer_group_id'])) {
            $customerGroup = $this->groupRepository->getById($data['customer_group_id']);
            $data['customer_tax_class_id'] = $customerGroup->getTaxClassId();
            $this->setRecollect(true);
        }

        $this->getQuote()->addData($data);

        return $this;
    }

    /**
     * Add product to current order quote
     * $product can be either product id or product model
     * $config can be either buyRequest config, or just qty
     *
     * @param int|\Magento\Catalog\Model\Product $product
     * @param array|float|int|\Magento\Framework\DataObject $config
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addProduct($product, $config = 1)
    {
        if (!is_array($config) && !$config instanceof \Magento\Framework\DataObject) {
            $config = ['qty' => $config];
        }
        $config = new \Magento\Framework\DataObject($config);

        $courseId = $this->request->getParam('course_id', false);

        if (!$product instanceof \Magento\Catalog\Model\Product) {
            $machineTypeId = '';
            if ($config->getIsMachineType()) {
                //don't use back order with subscription multiple machine
                $ids = explode('_', $product);
                if (count($ids) == 2) {
                    $machineTypeId = $ids[0];
                    $product = $ids[1];
                }
            }
            $productId = $product;
            $product = $this->_objectManager->create(
                \Magento\Catalog\Model\Product::class
            )->setStore(
                $this->getSession()->getStore()
            )->setStoreId(
                $this->getSession()->getStoreId()
            )->load(
                $product
            );
            if (!$product->getId()) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('We could not add a product to cart by the ID "%1".', $productId)
                );
            }
            $unitQty = $product->getData('unit_qty');
            $unitCase = $config->getData('case_display');;
            if ($caseDisplay = $product->getData('case_display')) {
                if (\Riki\CreateProductAttributes\Model\Product\CaseDisplay::CD_CASE_ONLY == $caseDisplay) {
                    $unitCase = \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE;
                }
            } else {
                $unitQty = 1;
            }

            if(\Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE == $unitCase){
                $qty = $product->getUnitQty() ? $product->getUnitQty() : 1;
                $unitQty = (int)($qty/($unitQty));
            }
            if ($config->getIsMachineType()) {
                $options = [
                    'machine_type_id' => $machineTypeId,
                    'qty' => $unitQty
                ];
                $config->setData('qty', $unitQty);
                $config->setData('options', $options);
                $config->setData('is_multiple_machine', true);
                $product->addCustomOption('machine_type_id', $machineTypeId);
                $product->setData('is_riki_machine', 1);
            }
        }
        if ($courseId) {
            $product->setData('is_subscription_product', 1);
        }

        $item = $this->quoteInitializer->init($this->getQuote(), $product, $config);

        if (is_string($item)) {
            throw new \Magento\Framework\Exception\LocalizedException(__($item));
        }
        $item->checkData();
        $this->setRecollect(true);

        return $this;
    }
}