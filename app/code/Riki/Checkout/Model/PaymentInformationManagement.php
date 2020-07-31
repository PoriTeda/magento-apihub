<?php

namespace Riki\Checkout\Model;

use Riki\Checkout\Api\PaymentInformationManagementInterface;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\Event\ManagerInterface;

class PaymentInformationManagement implements PaymentInformationManagementInterface
{

    /**
     * @var \Magento\Quote\Api\BillingAddressManagementInterface
     */
    protected $billingAddressManagement;

    /**
     * @var \Magento\Quote\Api\PaymentMethodManagementInterface
     */
    protected $paymentMethodManagement;
    /**
     * @var $quoteRepository \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;
    /**
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    protected $cartManagement;


    /**
     * @var \Magento\Checkout\Model\PaymentDetails
     */
    protected $paymentDetailsFactory;


    /**
     * @var \Magento\Quote\Api\CartTotalRepositoryInterface
     */
    protected $cartTotalsRepository;

    /**
     * @var $addressItemRelationship \Riki\Checkout\Model\AddressItemRelationship
     */
    protected $addressItemRelationship;

    /**
     * @var $orderRepositoryInterface \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepositoryInterface;

    /* @var \Magento\Framework\Event\ManagerInterface */
    protected $managerInterface;
    /**
     * @var \Riki\Questionnaire\Helper\Admin
     */
    protected $_questionnaireAdminHelper;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @var \Magento\Checkout\Api\ShippingInformationManagementInterface
     */
    protected $shippingInformationManagement;
    /**
     * @var \Riki\Questionnaire\Helper\Data
     */
    protected $dataHelper;
    /**
     * PaymentInformationManagement constructor.
     * @param ManagerInterface $managerInterface
     * @param \Magento\Quote\Api\BillingAddressManagementInterface $billingAddressManagement
     * @param \Magento\Quote\Api\PaymentMethodManagementInterface $paymentMethodManagement
     * @param \Magento\Quote\Api\CartManagementInterface $cartManagement
     * @param \Magento\Checkout\Model\PaymentDetailsFactory $paymentDetailsFactory
     * @param \Magento\Quote\Api\CartTotalRepositoryInterface $cartTotalsRepository
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepositoryInterface
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepositoryInterface
     * @param AddressItemRelationship $addressItemRelationship
     * @param \Riki\Questionnaire\Helper\Admin $questionnaireAdminHelper
     * @param Logger $loggerInterface
     */
    public function __construct(
        \Magento\Framework\Event\ManagerInterface $managerInterface,
        \Magento\Quote\Api\BillingAddressManagementInterface $billingAddressManagement,
        \Magento\Quote\Api\PaymentMethodManagementInterface $paymentMethodManagement,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Checkout\Model\PaymentDetailsFactory $paymentDetailsFactory,
        \Magento\Quote\Api\CartTotalRepositoryInterface $cartTotalsRepository,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepositoryInterface,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepositoryInterface,
        \Riki\Checkout\Model\AddressItemRelationship $addressItemRelationship,
        \Riki\Questionnaire\Helper\Admin $questionnaireAdminHelper,
        \Riki\Questionnaire\Helper\Data $dataHelper,
        \Magento\Framework\App\RequestInterface $request,
        Logger $loggerInterface,
        \Magento\Checkout\Api\ShippingInformationManagementInterface $shippingInformationManagement
    )
    {
        $this->managerInterface = $managerInterface;
        $this->billingAddressManagement = $billingAddressManagement;
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->cartManagement = $cartManagement;
        $this->paymentDetailsFactory = $paymentDetailsFactory;
        $this->cartTotalsRepository = $cartTotalsRepository;
        $this->quoteRepository = $cartRepositoryInterface;
        $this->orderRepositoryInterface = $orderRepositoryInterface;
        $this->addressItemRelationship = $addressItemRelationship;
        $this->logger = $loggerInterface;
        $this->_questionnaireAdminHelper = $questionnaireAdminHelper;
        $this->_request = $request;
        $this->shippingInformationManagement = $shippingInformationManagement;
        $this->dataHelper = $dataHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function savePaymentInformationAndPlaceOrder(
        $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    )
    {

        try {
            $this->savePaymentInformation($cartId, $paymentMethod, $billingAddress);

            $quote = $this->quoteRepository->get($cartId);
            $quote->setIsMultipleShipping(1);
            $this->dataHelper->logQuestionOrder('Begin Set param FO Multi Cart Id:'.$cartId);
            if ($paymentMethod->getExtensionAttributes() != null && $paymentMethod->getExtensionAttributes()->getQuestionare()) {
                $this->dataHelper->logQuestionOrder(' Area FO Multi Set param Cart Id:'.$cartId,$paymentMethod->getExtensionAttributes()->getQuestionare());
                $this->_request->setParams(['questionnaire' => $paymentMethod->getExtensionAttributes()->getQuestionare()]);
            }
            /* if place order sucessfully - try to save item-address relation ship */
            if ($orderId = $this->cartManagement->placeOrder($cartId)) {
                /* cause incorrect assignation warehouse -> RIKI-6545 */
                //$order = $this->orderRepositoryInterface->get($orderId);
                //$this->addressItemRelationship->saveOrderAddressItemRelation($quote,$order);
                //$this->dispathEvent('after_save_address_item_in_multi_checkout',array('order' => $order));
                $this->dataHelper->logQuestionOrder('Completed order Multi FO:'.$orderId);
                return $orderId;
            } else {
                throw new \Magento\Framework\Exception\CouldNotSaveException(__("Could not place order"));
            }
        } catch (\Magento\Framework\Exception\CouldNotSaveException $exception) {
            $this->logger->critical(__("Could not place order"));
            $this->logger->debug("Cart id {$cartId}");
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
     * {@inheritDoc}
     */
    public function saveShippingAndPaymentInformation(
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
    )
    {
        $this->shippingInformationManagement->saveAddressInformation($cartId, $addressInformation);
        $this->paymentMethodManagement->set($cartId, $paymentMethod);

        return $this->cartTotalsRepository->get($cartId);
    }

    public function dispathEvent($eventName, $data)
    {
        $this->managerInterface->dispatch($eventName, $data);
    }
}
