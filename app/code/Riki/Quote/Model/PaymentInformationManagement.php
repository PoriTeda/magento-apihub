<?php
namespace Riki\Quote\Model;

use Riki\Quote\Api\PaymentInformationManagementInterface;

class PaymentInformationManagement implements PaymentInformationManagementInterface{

    /**
     * @var \Magento\Quote\Api\BillingAddressManagementInterface
     */
    protected $billingAddressManagement;

    /**
     * @var \Magento\Quote\Api\PaymentMethodManagementInterface
     */
    protected $paymentMethodManagement;

    /**
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    protected $cartManagement;


    /**
     * @var PaymentDetailsFactory
     */
    protected $paymentDetailsFactory;


    /**
     * @var \Magento\Quote\Api\CartTotalRepositoryInterface
     */
    protected $cartTotalsRepository;



    protected $orderCollectionFactory;

    /**
     * @param \Magento\Quote\Api\BillingAddressManagementInterface $billingAddressManagement
     * @param \Magento\Quote\Api\PaymentMethodManagementInterface $paymentMethodManagement
     * @param \Magento\Quote\Api\CartManagementInterface $cartManagement
     * @param PaymentDetailsFactory $paymentDetailsFactory
     * @param \Magento\Quote\Api\CartTotalRepositoryInterface $cartTotalsRepository
     * @codeCoverageIgnore
     */

/*\Magento\Quote\Api\CartManagementInterface $cartManagement,*/
    public function __construct(
        \Magento\Quote\Api\BillingAddressManagementInterface $billingAddressManagement,
        \Magento\Quote\Api\PaymentMethodManagementInterface $paymentMethodManagement,
        \Riki\Quote\Model\QuoteManagement $cartManagement,
        \Magento\Checkout\Model\PaymentDetailsFactory $paymentDetailsFactory,
        \Magento\Quote\Api\CartTotalRepositoryInterface $cartTotalsRepository,
        \Magento\Sales\Model\OrderFactory $orderCollectionFactory
    ) {
        $this->billingAddressManagement = $billingAddressManagement;
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->cartManagement = $cartManagement;
        $this->paymentDetailsFactory = $paymentDetailsFactory;
        $this->cartTotalsRepository = $cartTotalsRepository;
        $this->orderCollectionFactory = $orderCollectionFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function savePaymentInformationAndPlaceOrder(
        $cartId,
        $mmData,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        try {
            $this->savePaymentInformation($cartId, $paymentMethod, $billingAddress);
            $orderID  = $this->cartManagement->placeOrder($cartId);
            $this->saveOrderDetail($orderID,$mmData);
            return $orderID;
        }
        catch(\Magento\Framework\Exception\CouldNotSaveException $exception ){
            throw $exception;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function savePaymentInformation(
        $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        if ($billingAddress) {
            $this->billingAddressManagement->assign($cartId, $billingAddress);
        }
        $this->paymentMethodManagement->set($cartId, $paymentMethod);

        return true;
    }

    /**
     * Update data by order id;
     * @param $orderId
     * @param $mmData
     */
    public function saveOrderDetail($orderId,$mmData){
        $order = $this->orderCollectionFactory->create()->load($orderId);
        if($order){
            if(isset($mmData['mm_order_id'])){
                $order->setMmOrderId($mmData['mm_order_id']);
            }
            if(isset($mmData['substitution'])){
                $order->setSubstitution($mmData['substitution']);
            }
            if(isset($mmData['mm_packing'])){
                $order->setMmPacking($mmData['mm_packing']);
            }
            if(isset($mmData['mm_cushioning'])){
                $order->setMmCushioning($mmData['mm_cushioning']);
            }
            if(isset($mmData['mm_broken_sku'])){
                $order->setMmBrokenSku($mmData['mm_broken_sku']);
            }
            if(isset($mmData['mm_broken_reason_code'])){
                $order->setMmBrokenReasonCode($mmData['mm_broken_reason_code']);
            }
            if(isset($mmData['mm_repair_company_name'])){
                $order->setMmRepairCompanyName($mmData['mm_repair_company_name']);
            }
            if(isset($mmData['mm_repair_company_postal_code'])){
                $order->setMmRepairCompanyPostalCode($mmData['mm_repair_company_postal_code']);
            }
            if(isset($mmData['mm_repair_company_prefecture'])){
                $order->setMmRepairCompanyPrefecture($mmData['mm_repair_company_prefecture']);
            }
            if(isset($mmData['mm_repair_company_address'])){
                $order->setMmRepairCompanyAddress($mmData['mm_repair_company_address']);
            }
            if(isset($mmData['mm_repair_phone_number'])){
                $order->setMmRepairPhoneNumber($mmData['mm_repair_phone_number']);
            }
            if(isset($mmData['delivery_date_period'])){
                $order->setDeliveryDatePeriod($mmData['delivery_date_period']);
            }
            if(isset($mmData['delivery_time'])){
                $order->setDeliveryTime($mmData['delivery_time']);
            }
            if(isset($mmData['order_date'])){
                $order->setOrderDate($mmData['order_date']);
            }
            if(isset($mmData['prior_phone_call_flg'])){
                $order->setPriorPhoneCallFlg($mmData['prior_phone_call_flg']);
            }
            if(isset($mmData['caution_for_couterior'])){
                $order->setCautionForCouterior($mmData['caution_for_couterior']);
            }
            $order->save();
        }
    }




}
