<?php

namespace Bluecom\Paygent\Observer;

class AuthorizeAfterAssignationSuccess implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Bluecom\Paygent\Model\PaygentFactory
     */
    protected $paygentFactory;

    /**
     * @var null|\Bluecom\Paygent\Model\Paygent
     */
    protected $paygentModel = null;

    protected $authorizeData = [];

    /**
     * @var \Riki\SubscriptionMachine\Model\MonthlyFeeProfile\Validator
     */
    private $validatorMonthlyFee;

    /**
     * @var \Riki\SubscriptionMachine\Helper\MonthlyFee\PaygentHelper
     */
    private $paygentHelper;

    /**
     * @var \Magento\Framework\Url
     */
    private $urlHelper;

    /**
     * AuthorizeAfterAssignationSuccess constructor.
     *
     * @param \Bluecom\Paygent\Model\PaygentFactory $paygentFactory
     */
    public function __construct(
        \Bluecom\Paygent\Model\PaygentFactory $paygentFactory,
        \Riki\SubscriptionMachine\Model\MonthlyFeeProfile\Validator $validatorMonthlyFee,
        \Riki\SubscriptionMachine\Helper\MonthlyFee\PaygentHelper $paygentHelper,
        \Magento\Framework\Url $urlHelper
    ) {
        $this->paygentFactory = $paygentFactory;
        $this->validatorMonthlyFee = $validatorMonthlyFee;
        $this->paygentHelper = $paygentHelper;
        $this->urlHelper = $urlHelper;
    }

    /**
     * get authorize data for order choose authorize without redirect
     *
     * @return array
     */
    public function getAuthorizeData()
    {
        return $this->authorizeData;
    }

    /**
     * get authorize data for order choose authorize without redirect
     *
     * @param $key
     * @param $value
     */
    public function setAuthorizeData($key, $value)
    {
        $this->authorizeData[$key] = $value;
    }

    /**
     * Authorize for order which choose without redirect method
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Bluecom\Paygent\Exception\PaygentAuthorizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        /*reject simulate process*/
        if ($order instanceof \Riki\Subscription\Model\Emulator\Order) {
            return;
        }

        if (!$order instanceof \Magento\Sales\Model\Order || !$order->getId()) {
            return;
        }

        $authorizeData = $this->getAuthorizeData();

        if (empty($authorizeData)) {
            if($this->validatorMonthlyFee->isMonthlyFeeProfile($order->getProfileId())) {
                $this->generateRedirectLink($order, $order->getPayment(), floor($order->getGrandTotal()));
            }
            return;
        }

        if (!isset($authorizeData[$order->getIncrementId()])
            || empty($authorizeData[$order->getIncrementId()])
        ) {
            return;
        }

        $data = $authorizeData[$order->getIncrementId()];

        if (!isset($data['payment'])
            || !isset($data['amount'])
            || !isset($data['lastTradingId'])
        ) {
            return;
        }

        $payment = $data['payment'];

        $amount = $data['amount'];

        $lastTradingId = $data['lastTradingId'];

        /*reset authorize data to prevent authorization process run twice*/
        $this->setAuthorizeData($order->getIncrementId(), []);

        $authorize = $this->getPaymentModel()->authorizeWithoutRedirect($payment, $amount, $lastTradingId);

        if ($authorize !== true) {
            if ($order->getProfileId() && $this->validatorMonthlyFee->isMonthlyFeeProfile($order->getProfileId())) {
                $this->generateRedirectLink($order, $payment, $amount);
            } else {
                $paymentOrder = $payment->getOrder();

                $paymentErrorCode = $paymentOrder ? $paymentOrder->getPaymentErrorCode() : '';

                throw new \Bluecom\Paygent\Exception\PaygentAuthorizedException(__(
                    'Order use previous card number for authorizing failure. ' .
                    'Please change payment method or use new credit card',
                    $paymentErrorCode
                ));
            }
        } else {
            $order->save();
        }
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $payment
     * @param $amount
     * @throws \Bluecom\Paygent\Exception\PaygentAuthorizedException
     */
    private function generateRedirectLink($order, $payment, $amount)
    {
        $params = [
            'return_url' => $this->urlHelper->getUrl('', [ '_scope' => $order->getStoreId(), '_nosid' => true ]),
            'inform_url' => $this->urlHelper->getUrl('paygent/paygent/response', [ '_scope' => $order->getStoreId(), '_nosid' => true ])
        ];

        $res = $this->getPaymentModel()->initRedirectLink($order->getIncrementId(), $amount, $params);

        if ($res['result'] == 0) {
            //set payment info
            $payment->setPaygentLimitDate($res['limit_date']);
            $payment->setPaygentUrl($res['url']);

            $order->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT)
                ->setStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);

            //Send Paygent Url email to customer
            $this->paygentHelper->sendPaygenUrlEmailToCustomer($order->getCustomerEmail(), $res['url'], $order->getStoreId());

            $order->save();
        } else {
            throw new \Bluecom\Paygent\Exception\PaygentAuthorizedException(__(
                'Cannot generate Paygent redirect link.'
            ));
        }
    }

    private function getPaymentModel()
    {
        if($this->paygentModel == null) {
            $this->paygentModel = $this->paygentFactory->create();
        }

        return $this->paygentModel;
    }
}
