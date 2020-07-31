<?php

namespace Riki\SubscriptionMachine\Cron;

use Magento\Sales\Model\Order;

class RegeneratePaygentUrlForPendingPaymentOrder
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory
     */
    private $paymentCollectionFactory;

    /**
     * @var \Riki\SubscriptionMachine\Helper\MonthlyFee\PaygentHelper
     */
    private $monthlyFeePaygentHelper;

    /**
     * @var \Bluecom\Paygent\Model\PaygentFactory
     */
    private $paygentFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $timezoneInterface;

    /**
     * @var \Magento\Framework\Url
     */
    private $urlHelper;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory $paymentCollectionFactory,
        \Riki\SubscriptionMachine\Helper\MonthlyFee\PaygentHelper $monthlyFeePaygentHelper,
        \Bluecom\Paygent\Model\PaygentFactory $paygentFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        \Magento\Framework\Url $urlHelper
    ) {

        $this->scopeConfig = $scopeConfig;
        $this->paymentCollectionFactory = $paymentCollectionFactory;
        $this->paygentFactory = $paygentFactory;
        $this->monthlyFeePaygentHelper = $monthlyFeePaygentHelper;
        $this->timezoneInterface = $timezoneInterface;
        $this->urlHelper = $urlHelper;
    }


    public function execute()
    {
        /** @var \Magento\Sales\Model\ResourceModel\Order\Payment\Collection $paymentCollection */
        $paymentCollection = $this->paymentCollectionFactory->create();

        $paymentCollection->getSelect()
            ->joinInner(
                ['order' => 'sales_order'],
                'main_table.parent_id = order.entity_id',
                [
                    'order_increment_id' => 'order.increment_id',
                    'order_store_id' => 'order.store_id',
                    'order_grand_total' => 'order.grand_total',
                    'customer_email' => 'order.customer_email',
                ]
            )->joinInner(
                ['profile' => 'subscription_profile'],
                'order.subscription_profile_id = profile.profile_id',
                []
            )->joinInner(
                ['course' => 'subscription_course'],
                'profile.course_id = course.course_id',
                []
            )->where(
                'course.subscription_type = ?', \Riki\SubscriptionCourse\Model\Course\Type::TYPE_MONTHLY_FEE
            )->where(
                'main_table.paygent_limit_date IS NOT NULL'
            )->where(
                new \Zend_Db_Expr(sprintf("STR_TO_DATE(main_table.paygent_limit_date, '%%Y%%m%%d%%H%%i%%s') < '%s'",
                    $this->getDateAfter()))
            )->where(
                new \Zend_Db_Expr(sprintf("order.status = '%s'", Order::STATE_PENDING_PAYMENT))
            );


        if ($paymentCollection->getSize() > 0) {
            $bindingData = [];

            /** @var \Bluecom\Paygent\Model\Paygent $paygentModel */
            $paygentModel = $this->paygentFactory->create();
            /** @var \Magento\Sales\Model\Order\Payment $payment */
            foreach ($paymentCollection as $payment) {

                $params = [
                    'return_url' => $this->urlHelper->getUrl('', [ '_scope' => $payment->getData('order_store_id'), '_nosid' => true ]),
                    'inform_url' => $this->urlHelper->getUrl('paygent/paygent/response', [ '_scope' => $payment->getData('order_store_id'), '_nosid' => true ])
                ];

                $res = $paygentModel->initRedirectLink($payment->getData('order_increment_id') . $this->getRandomString(),
                    floor($payment->getData('order_grand_total')), $params);
                if ($res['result'] == 0) {
                    //set payment info
                    $bindingData[$payment->getEntityId()] = [
                        'paygent_url' => $res['url'],
                        'paygent_limit_date' => $res['limit_date']
                    ];

                    //Send Paygent Url email to customer
                    $this->monthlyFeePaygentHelper->sendPaygenUrlEmailToCustomer($payment->getData('customer_email'),
                        $res['url'], $payment->getData('order_store_id'));
                }
            }

            if (!empty($bindingData)) {
                $this->updatePaymentInformation($paymentCollection, $bindingData);
            }
        }

    }

    /**
     * @param \Magento\Sales\Model\ResourceModel\Order\Payment\Collection $paymentCollection
     * @param mixed $bindingData
     */
    protected function updatePaymentInformation($paymentCollection, $bindingData)
    {
        $connection = $paymentCollection->getResource()->getConnection();

        $paygentUrlConditions = [];
        $limitDateConditions = [];


        foreach ($bindingData as $paymentId => $data) {
            $case = $connection->quoteInto('?', $paymentId);
            $paygentUrlResult = $connection->quoteInto("?", $data['paygent_url']);
            $limitDateResult = $connection->quoteInto("?", $data['paygent_limit_date']);
            $paygentUrlConditions[$case] = $paygentUrlResult;
            $limitDateConditions[$case] = $limitDateResult;
        }

        $paygentUrlValue = $connection->getCaseSql('entity_id', $paygentUrlConditions, 'paygent_url');
        $limitDateValue = $connection->getCaseSql('entity_id', $limitDateConditions, 'paygent_limit_date');
        $where = ['entity_id IN (?)' => array_keys($bindingData)];

        $connection->beginTransaction();
        $connection->update($paymentCollection->getMainTable(), ['paygent_url' => $paygentUrlValue, 'paygent_limit_date' => $limitDateValue], $where);
        $connection->commit();

    }

    /**
     * Get the date after for comparing with the expired order.
     *
     * @return string
     */
    protected function getDateAfter()
    {
        $timeZone = $this->timezoneInterface->getConfigTimezone();
        $contractDate = $this->timezoneInterface->date()->setTimezone(new \DateTimeZone($timeZone))->format('Y-m-d H:i:s');

        return $contractDate;
    }

    private function getRandomString()
    {
        return 'MF' . strtotime(date('Y-m-d H:i:s'));
    }
}