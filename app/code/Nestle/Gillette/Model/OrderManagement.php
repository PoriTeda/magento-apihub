<?php

namespace Nestle\Gillette\Model;

use Bluecom\Paygent\Model\ResourceModel\PaygentOption\CollectionFactory;
use Magento\Customer\Api\AddressRepositoryInterface;
use Nestle\Gillette\Api\Data\CartEstimationInterface;
use Nestle\Purina\Api\DeliveryDateInterface;
use Nestle\Gillette\Api\Data\CartEstimationResultInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Riki\DeliveryType\Model\DeliveryDate;
use Magento\Payment\Model\MethodList;

/**
 * Class CartEstimationManagement
 * @package Nestle\Gillette\Model
 */
Class OrderManagement
    implements \Nestle\Gillette\Api\OrderManagementInterface
{
    /**
     * @var Validator
     */
    protected $gilletteValidator;

    /**
     * @var AddressRepositoryInterface
     */
    protected $customerAddressRepository;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Nestle\Gillette\Api\Data\CartEstimationResultInterfaceFactory
     */
    protected $cartEstimationResultFactory;

    /**
     * @var \Nestle\Purina\Api\DeliveryDateInterface
     */
    protected $deliveryDateInterface;

    /**
     * @var \Bluecom\PaymentFee\Model\PaymentFeeFactory
     */
    protected $paymentFeeFactory;

    /**
     * @var \Riki\Coupons\Helper\Coupon
     */
    protected $couponHelper;
    /**
     * @var \Riki\DeliveryType\Model\DeliveryDate
     */
    protected $deliveryDateModel;

    /**
     * @var \Riki\Subscription\Helper\Order\Data
     */
    protected $subscriptionHelperData;

    /**
     * @var \Magento\Payment\Model\MethodList
     */
    protected $methodList;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * @var \Nestle\Gillette\Helper\Data
     */
    public $gilletteHelper;

    /**
     * @var Bluecom\Paygent\Model\ResourceModel\PaygentOption\CollectionFactory $collectionFactory
     */
    protected $paygentOptioncollectionFactory;

    public function __construct(
    Validator $validator,
    AddressRepositoryInterface $customerAddressRepository,
    ProductRepositoryInterface $productRepository,
    CartEstimationResultInterfaceFactory $cartEstimationResultFactory,
    DeliveryDateInterface $deliveryDateInterface,
    \Bluecom\PaymentFee\Model\PaymentFeeFactory $paymentFeeFactory,
    DeliveryDate $deliveryDateModel,
    \Riki\Subscription\Helper\Order\Data $subscriptionHelperData,
    MethodList $methodList,
    \Psr\Log\LoggerInterface $logger,
    \Nestle\Gillette\Helper\Data $gilletteHelper,
    \Bluecom\Paygent\Model\ResourceModel\PaygentOption\CollectionFactory $paygentOptioncollectionFactory
    ){
        $this->gilletteValidator = $validator;
        $this->customerAddressRepository = $customerAddressRepository;
        $this->productRepository = $productRepository;
        $this->cartEstimationResultFactory = $cartEstimationResultFactory;
        $this->deliveryDateInterface =  $deliveryDateInterface;
        $this->paymentFeeFactory = $paymentFeeFactory;
        $this->deliveryDateModel = $deliveryDateModel;
        $this->subscriptionHelperData = $subscriptionHelperData;
        $this->methodList = $methodList;
        $this->logger = $logger;
        $this->gilletteHelper = $gilletteHelper;
        $this->paygentOptioncollectionFactory = $paygentOptioncollectionFactory;
    }

    /**
     * @param CartEstimationInterface $cartEstimation
     * @return array|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function placeOrder(CartEstimationInterface $cartEstimation)
    {
        $logInfo = [];
        $request = clone $cartEstimation;
        $paymentMethod = $cartEstimation->getPaymentMethod();
        if ($paymentMethod) {
            $request->setPaymentMethod($paymentMethod->getData());
        }
        $consumerDbId = $cartEstimation->getConsumerDbId();
        /** @var \Magento\Customer\Api\Data\CustomerInterface $customer */
        $customer = $this->gilletteValidator->getCustomerByConsumerDbId($consumerDbId);
        $this->gilletteValidator->validateData($cartEstimation, 'order');
        $this->gilletteHelper->buildRequestLog($request, 'OrderManagement');
        $order = $this->subscriptionHelperData->createMageOrderForGillette($cartEstimation, $customer);
        $result = [];
        if ($order instanceof  \Magento\Sales\Model\Order) {
            $result['order_no'] = $order->getIncrementId();
            $result['order_status'] = $order->getStatus();
            $result['redirect_url'] = null;
            if ($order->getPayment()->getMethod() == 'paygent') {
                $result['redirect_url'] = $this->getRedirectUrl($order);
            }
        }
        $this->logger->info('OrderManagement response::'.json_encode($result, JSON_UNESCAPED_UNICODE));
        return [$result];
    }

    /**
     * Get Paygent Redirect URL
     * @param $order
     * @return mixed
     */
    public function getRedirectUrl($order) {
        $redirectUrl = null;
        $payment = $order->getPayment();
        $redirectUrl = $payment->getPaygentUrl();
        return $redirectUrl;
    }
}
