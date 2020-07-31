<?php
namespace Riki\Quote\Model;

use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Framework\Exception\StateException;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Quote\Model\QuoteValidator as QuoteValidator;
use Magento\Sales\Api\Data\OrderInterfaceFactory as OrderFactory;
use Magento\Sales\Api\OrderManagementInterface as OrderManagement;
use Magento\Quote\Model\Quote\Address\ToOrder as ToOrderConverter;
use Magento\Quote\Model\Quote\Address\ToOrderAddress as ToOrderAddressConverter;
use Magento\Quote\Model\Quote\Item\ToOrderItem as ToOrderItemConverter;
use Magento\Quote\Model\Quote\Payment\ToOrderPayment as ToOrderPaymentConverter;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResourceModel;


class QuoteManagement extends \Magento\Quote\Model\QuoteManagement
{
    const IS_ACTIVE   = 1;
    const IS_INACTIVE = 0;

    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @var QuoteResourceModel
     */
    protected $quoteResourceModel;

    public function __construct(
        EventManager $eventManager,
        QuoteValidator $quoteValidator,
        OrderFactory $orderFactory,
        OrderManagement $orderManagement,
        \Magento\Quote\Model\CustomerManagement $customerManagement,
        ToOrderConverter $quoteAddressToOrder,
        ToOrderAddressConverter $quoteAddressToOrderAddress,
        ToOrderItemConverter $quoteItemToOrderItem,
        ToOrderPaymentConverter $quotePaymentToOrderPayment,
        UserContextInterface $userContext,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\CustomerFactory $customerModelFactory,
        \Magento\Quote\Model\Quote\AddressFactory $quoteAddressFactory,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\AccountManagementInterface $accountManagement,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        QuoteResourceModel $quoteResourceModel
    ){
        parent::__construct(
            $eventManager,
            $quoteValidator,
            $orderFactory,
            $orderManagement,
            $customerManagement,
            $quoteAddressToOrder,
            $quoteAddressToOrderAddress,
            $quoteItemToOrderItem,
            $quotePaymentToOrderPayment,
            $userContext,
            $quoteRepository,
            $customerRepository,
            $customerModelFactory,
            $quoteAddressFactory,
            $dataObjectHelper,
            $storeManager,
            $checkoutSession,
            $customerSession,
            $accountManagement,
            $quoteFactory
        );

        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->quoteResourceModel = $quoteResourceModel;
    }



    public function assignCustomer($cartId, $customerId, $storeId)
    {
        $quote         = $this->quoteRepository->get($cartId);
        $customer      = $this->customerRepository->getById($customerId);
        $customerModel = $this->customerModelFactory->create();

        if (!in_array($storeId, $customerModel->load($customerId)->getSharedStoreIds())) {
            throw new StateException(
                __('Cannot assign customer to the given cart. The cart belongs to different store.')
            );
        }

        $quote->setCustomer($customer);
        $quote->setCustomerIsGuest(0);

        //set cart inactive;
        $quote->setIsActive(QuoteManagement::IS_INACTIVE);

        $quoteIdMaskFactory = $this->getQuoteIdMaskFactory();
        /** @var  \Magento\Quote\Model\QuoteIdMask $quoteIdMask */
        $quoteIdMask = $quoteIdMaskFactory->create()->load($cartId, 'quote_id');
        if ($quoteIdMask->getId()) {
            $quoteIdMask->delete();
        }
        $this->quoteRepository->save($quote);
        return true;
    }

    private function getQuoteIdMaskFactory()
    {
        return $this->quoteIdMaskFactory;
    }



    /**
     * {@inheritdoc}
     */
    public function placeOrder($cartId, PaymentInterface $paymentMethod = null)
    {
        $quote = $this->quoteRepository->get($cartId);
        if ($paymentMethod) {
            $paymentMethod->setChecks([
                \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_CHECKOUT,
                \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_FOR_COUNTRY,
                \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_FOR_CURRENCY,
                \Magento\Payment\Model\Method\AbstractMethod::CHECK_ORDER_TOTAL_MIN_MAX,
                \Magento\Payment\Model\Method\AbstractMethod::CHECK_ZERO_TOTAL,
            ]);
            $quote->getPayment()->setQuote($quote);

            $data = $paymentMethod->getData();
            if (isset($data['additional_data'])) {
                $data = array_merge($data, (array)$data['additional_data']);
                unset($data['additional_data']);
            }
            $quote->getPayment()->importData($data);
        }
       if ($quote->getCheckoutMethod() === self::METHOD_GUEST) {
            $quote->setCustomerId(null);
            $quote->setCustomerEmail($quote->getBillingAddress()->getEmail());
            $quote->setCustomerIsGuest(true);
            $quote->setCustomerGroupId(\Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID);
        }

        //$this->eventManager->dispatch('checkout_submit_before', ['quote' => $quote]);

        $order = $this->submit($quote);
        if (null == $order) {
            throw new LocalizedException(__('Cannot place order.'));
        }

        $this->checkoutSession->setLastQuoteId($quote->getId());
        $this->checkoutSession->setLastSuccessQuoteId($quote->getId());
        $this->checkoutSession->setLastOrderId($order->getId());
        $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
        $this->checkoutSession->setLastOrderStatus($order->getStatus());

        $this->eventManager->dispatch('checkout_submit_all_after', ['order' => $order, 'quote' => $quote]);
        return $order->getId();
    }


    /**
     * Set trigger_recollect to all active quotes to force Magento cart re-collect
     * total price, Promotion when the AJAX total-information does not work
     *
     * @param array $additionalConditions
     *
     * @return void
     *
     * @throws \Exception
     */
    public function triggerRecollectActiveQuote($additionalConditions = [])
    {
        try {
            $where = ['is_active = ?' => 1, 'trigger_recollect = ?' => 0];

            if ($additionalConditions) {
                $where = array_merge($where, $additionalConditions);
            }

            $this->quoteResourceModel->getConnection()->update(
                $this->quoteResourceModel->getTable('quote'),
                ['trigger_recollect' => 1],
                $where
            );
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
