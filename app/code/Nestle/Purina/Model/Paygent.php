<?php
/**
 * Nestle Purina Vets
 * PHP version 7
 *
 * @category Nestle_Purina
 * @package  Nestle\Purina
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://vets.nestle.jp
 */
namespace Nestle\Purina\Model;

use Riki\Loyalty\Model\RewardManagement;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus;

/**
 * Class Paygent
 *
 * @category Nestle_Purina
 * @package  Nestle\Purina
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://vets.nestle.jp
 */
class Paygent extends \Bluecom\Paygent\Model\Paygent
{
    /**
     * Initiliaze payment, in term of Purina request, always new paygent CC
     *
     * @param string $paymentAction payment action
     * @param object $stateObject   state object
     *
     * @return $this
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function initialize($paymentAction, $stateObject)
    {
        $order = $this->getInfoInstance()->getOrder();
        $amount = floor($order->getGrandTotal());
        if ($this->rewardManagement->isSpecialCase(
            $order->getQuoteId(), $order->getStoreId()
        )) {
            $amount = RewardManagement::VALIDATE_CARD_AMOUNT;
        }
        $payment = $order->getPayment();

        //default for credit card paygent set to PENDING PAYMENT
        $state = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;

        //get paygent option
        $currentOption = $this->getOptionPaygent();
        $paygentOption = $currentOption->getOptionCheckout();

        $lastTradingId = null;
        //if customer choose Paygent Option is pay without redirect
        if (!$paygentOption) {
            //get last trading id
            $customerId = $order->getCustomerId();
            if ($customerId && !$order->getCustomerIsGuest()) {
                //get last trading id of customer
                $lastTradingId = $this->canReAuthorization($customerId);
            }
        }
        if ($lastTradingId) {
            $authorizationData = [
                'payment' => $payment,
                'amount' => $amount,
                'lastTradingId' => $lastTradingId
            ];
            $this->authorizeAfterAssignation->setAuthorizeData(
                $order->getIncrementId(), $authorizationData
            );
            $this->_eventManager->dispatch(
                'paygent_init_authorization_data_after', [
                'order' => $order,
                'authorization_data'   =>  $authorizationData
                ]
            );
            if ($stateObject->getStatus() != OrderStatus::STATUS_ORDER_IN_PROCESSING
            ) {
                $stateObject->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
                $stateObject->setStatus(OrderStatus::STATUS_ORDER_NOT_SHIPPED);
            }
            $stateObject->setIsNotified(false);
            return $this;
        } else {
            //init redirect link of paygent
            $params['return_url'] = $this->_scopeConfig->getValue(
                'purina/general/return_url'
            );
            for ($i = 0; $i < 10; $i++) {
                $res = $this->initRedirectLink(
                    $order->getIncrementId(), $amount, $params
                );
                if ($res['result'] == 0) {
                    //set payment info
                    $payment->setPaygentLimitDate($res['limit_date']);
                    $payment->setPaygentUrl($res['url']);

                    //set states and status for new order with paygent
                    $stateObject->setState($state);
                    $stateObject->setStatus(
                        \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT
                    );
                    $stateObject->setIsNotified(false);
                    /* Send new order transactional email for
                    Paygent only when Paygent sent back with success.*/
                    $order->setCanSendNewEmailFlag(false);
                    try {
                        //redirect url received from paygent
                        $currentOption->setData(
                            [
                            'customer_id' => $order->getCustomerId(),
                            'option_checkout' => 1,
                            'link_redirect' => $res['url']
                            ]
                        );
                        $currentOption->save();
                    } catch (\Exception $e) {
                        $message = sprintf(
                            'Authorization process has an error. error detail is %s.',
                            $e->getMessage()
                        );
                        throw new \Magento\Framework\Exception\LocalizedException(
                            __($message)
                        );
                    }
                    return $this;
                } else {
                    $errorCode = $res['response_code'];
                    $errorDetail = $res['response_detail'];
                    if ($errorCode == 'P010') {
                        //P010 fetch new increment id when transaction is exists
                        $quote = $this->checkoutSession->getQuote();
                        $quote->reserveOrderId();
                        $order->setIncrementId($quote->getReservedOrderId());
                        continue 1;
                    } else {
                        $message = sprintf(
                            'Authorization process has an error. error code is %s, error detail is %s.',
                            $errorCode,
                            $errorDetail
                        );
                        throw new \Magento\Framework\Exception\LocalizedException(
                            __($message)
                        );
                    }
                }
            }
        }
        return $this;
    }
}
