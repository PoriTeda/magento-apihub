<?php

namespace Riki\Sales\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Riki\Questionnaire\Model\AnswersFactory;
use Riki\Sales\Model\ResourceModel\Order\OrderAdditionalInformation as AdditionalInformationResourceModel;

class CheckoutSubmitAllAfter implements ObserverInterface
{
    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $_quoteSession;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;
    /**
     * @var \magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;
    /**
     * @var \Riki\Sales\Helper\Email $rikiSaleEmail
     */
    protected $rikiSaleEmail;
    /**
     * @var \Riki\Questionnaire\Helper\Admin
     */
    protected $_questionnaireAdminHelper;
    /**
     * @var \Riki\Sales\Helper\Admin
     */
    protected $_salesAdminHelper;
    /**
     * @var \Riki\User\Model\User $userAdmin
     */
    protected $userAdmin;
    /**
     * @var \Riki\SubscriptionMembership\Model\Customer\Attribute\Source\Membership $memberShipModel
     */
    protected $memberShipModel;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;

    /**
     * @var AdditionalInformationResourceModel
     */
    protected $orderAdditionalInformationResource;

    /**
     * CheckoutSubmitAllAfter constructor.
     *
     * @param \Magento\Backend\Model\Session\Quote $quoteSession
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Riki\Questionnaire\Helper\Admin $questionnaireAdminHelper
     * @param \Riki\Sales\Helper\Admin $salesAdminHelper
     * @param \Riki\Sales\Helper\Email $rikiSaleEmail
     * @param \Magento\Backend\Model\Auth\Session $userAdmin
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Riki\SubscriptionMembership\Model\Customer\Attribute\Source\Membership $membership
     * @param AdditionalInformationResourceModel $orderAdditionalInformationResource
     */
    public function __construct(
        \Magento\Backend\Model\Session\Quote $quoteSession,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\App\RequestInterface $request,
        \Riki\Questionnaire\Helper\Admin $questionnaireAdminHelper,
        \Riki\Sales\Helper\Admin $salesAdminHelper,
        \Riki\Sales\Helper\Email $rikiSaleEmail,
        \Magento\Backend\Model\Auth\Session $userAdmin,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Riki\SubscriptionMembership\Model\Customer\Attribute\Source\Membership $membership,
        AdditionalInformationResourceModel $orderAdditionalInformationResource
    )
    {
        $this->_quoteSession = $quoteSession;
        $this->_request = $request;
        $this->_customerFactory = $customerFactory;
        $this->_questionnaireAdminHelper = $questionnaireAdminHelper;
        $this->_salesAdminHelper = $salesAdminHelper;
        $this->rikiSaleEmail = $rikiSaleEmail;
        $this->userAdmin = $userAdmin;
        $this->_logger = $logger;
        $this->_messageManager = $messageManager;
        $this->memberShipModel = $membership;
        $this->orderAdditionalInformationResource = $orderAdditionalInformationResource;
    }

    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var Quote $quote */
        $quote = $observer->getEvent()->getQuote();

        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        /**
         * Save questionnaire
         */
        $dataAnswers = $this->_request->getParam('questionnaire');
        $showDataAnswers = $this->_request->getParam('questionnaire_show_in_admin');

        if (isset($dataAnswers) && !empty($dataAnswers) && isset($showDataAnswers) && $showDataAnswers == 1) {
            $this->_questionnaireAdminHelper->saveAnswersCreatedOrderAdmin($order, $dataAnswers);
        }
        $isFreeShipping = $this->_quoteSession->getFreeShippingFlag();
        $isFreeCharge = $this->_quoteSession->getFreeSurcharge();
        if ($isFreeShipping == 1 || $isFreeCharge == 1) {
            $orderID = $order->getIncrementId();
            $customerID = $quote->getCustomerId();
            $membership = '';
            $customerMembershipType = explode(',', $quote->getCustomerMembership());
            if (is_array($customerMembershipType)) {
                $end = end($customerMembershipType);
                foreach ($customerMembershipType as $v) {
                    $membership .= $this->memberShipModel->getOptionText($v);
                    if ($v != $end) {
                        $membership .= ',';
                    }
                }
            }

            // Order type is free samples => Create record for sales_order_additional_information
            $request = $this->_request->getParam('order');
            if ($request['charge_type'] == \Riki\Sales\Model\Config\Source\OrderType::ORDER_TYPE_FREE_SAMPLE) {
                $this->createOrderAdditionalInformation($order->getId(), $request);
            }

            $userId = $this->userAdmin->getUser()->getId();

            try {
                $this->rikiSaleEmail->sendMailFreePayFeeFreeShipFee(
                    [
                        'order_id' => $orderID,
                        'member_ship_type' => $membership,
                        'user_id' => $userId,
                        'customer_id' => $customerID
                    ]);
            } catch (\Exception $e) {
                $this->_logger->critical($e);
                $this->_messageManager->addWarning(
                    __('The free payment fee/ free shipping fee email was not sent. Please check your email settings.')
                );
            }
        }
    }

    public function createOrderAdditionalInformation($orderId, $request)
    {
        $data = [
            'order_id' => $orderId,
            'shipping_reason' => $request['shipping_reason'],
            'shipping_cause' => $request['shipping_cause']
        ];

        $connection = $this->orderAdditionalInformationResource->getConnection();
        try {
            $connection->insertOnDuplicate($connection->getTableName('sales_order_additional_information'), $data);
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }
}