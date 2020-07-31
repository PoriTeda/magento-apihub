<?php

namespace Bluecom\Paygent\Cron;
use Magento\Framework\Exception\NoSuchEntityException;
use  Magento\Framework\Exception\LocalizedException;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus;
use Riki\SubscriptionCourse\Model\Course\Type as CourseType;

class CancelOrders
{
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var \Bluecom\Paygent\Logger\Logger
     */
    protected $logger;

    /**
     * @var \Bluecom\Paygent\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $_customerRepository;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * CancelOrders constructor.
     * @param \Bluecom\Paygent\Helper\Data $dataHelper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Bluecom\Paygent\Logger\Logger $logger
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Bluecom\Paygent\Helper\Data $dataHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Bluecom\Paygent\Logger\Logger $logger,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    )
    {
        $this->dataHelper = $dataHelper;
        $this->dateTime = $dateTime;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->_customerRepository = $customerRepository;
        $this->logger = $logger;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
    }

    /**
     * Cron cancel order
     *
     * @return $this
     */
    public function execute()
    {
        $this->logger->info('======== START =========');
        $this->logger->info('Cancel Orders Cron running');

        //Check module enable
        if (!$this->dataHelper->isEnable()) {
            return $this;
        }

        //Get config values
        $statesConfig = $this->dataHelper->getStatesConfig();
        if (!$statesConfig) {
            return $this;
        }

        //Check having any orders cancelled.
        $isCancel = false;

        $date = $this->getDateAfter();

        /* @var $orders \Magento\Sales\Model\ResourceModel\Order\Collection */
        $orders = $this->orderCollectionFactory->create()
            ->addFieldToFilter('main_table.created_at', ['lteq' => $date])
            ->addFieldToFilter('main_table.status', ['in' => $statesConfig]);

        $orders->getSelect()
            ->joinLeft(
                ['reauthorize' => $orders->getTable('riki_authorization_timing')],
                'main_table.entity_id = reauthorize.order_id',
                ['reauthorize_order_id' => 'reauthorize.order_id']
            )->joinLeft(
                ['profile' => $orders->getTable('subscription_profile')],
                "main_table.subscription_profile_id = profile.profile_id",
                []
            )->joinLeft(
                ['course' => 'subscription_course'],
                'profile.course_id = course.course_id',
                ['subscription_type']
            )->having('reauthorize_order_id IS NULL');

        foreach ($orders as $order) {

            if ($order->getSubscriptionType() == CourseType::TYPE_MONTHLY_FEE) {
                continue;
            }

            try {
                $customer = $this->_customerRepository->getById($order->getCustomerId());
                $customAttribute = $customer->getCustomAttribute('consumer_db_id');

                if (!$customAttribute) {
                    $this->logger->info('Error when cancel order, Customer code for not found  for Order: ' . $order->getIncrementId());
                } else {
                    $order->cancel();
                    $order->setIsNotified(false);
                    $order->addStatusToHistory(
                        \Riki\Sales\Model\ResourceModel\Sales\Grid\OrderStatus::STATUS_ORDER_CANCELED,
                        __('Canceled by the Cron Cancellation Order Paygent After X Hours'),
                        false
                    );

                    $order->save();
                    $isCancel = true;

                    if ($this->dataHelper->isEnableSendEmail()) {
                        $this->sendEmail($this->dataHelper->getTemplateEmail(), $customer);
                    }
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->logger->info($e->getMessage());
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $this->logger->info('Error when cancel order IncrementId: ' . $order->getIncrementId());
            }

        }

        if (!$isCancel) {
            $this->logger->info('There is no order cancelled.');
        }
        $this->logger->info('======== END =========');

        return $this;
    }

    /**
     * Get the date after subtracting hours config value
     * that use for comparing with the created order.
     *
     * @return string
     */
    protected function getDateAfter()
    {
        //Get hours value config
        $hours = $this->dataHelper->getCancelHours();
        $seconds = $hours * 3600;

        $date = time() - $seconds;

        return date('Y-m-d H:i:s', $date);
    }

    public function sendEmail($templateId, $customer)
    {
        $senderInfo = $this->dataHelper->getSenderEmail();
        $transport = $this->transportBuilder->setTemplateIdentifier(
            $templateId
        )->setTemplateOptions(
            ['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->storeManager->getStore()->getId()]
        )->setTemplateVars(
            []
        )->setFrom(
            $senderInfo
        )->addTo(
            $customer->getEmail()
        )->getTransport();
        $transport->sendMessage();
    }
}