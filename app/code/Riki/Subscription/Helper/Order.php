<?php
namespace Riki\Subscription\Helper;

use Magento\Framework\App\Helper\Context;
use Riki\SubscriptionCourse\Model\Course\Type as SubCourseType;

class Order extends \Magento\Framework\App\Helper\AbstractHelper
{
    const AMOUNT_THRESHOLD_CART_RULE_TYPES = [
        'cart_fixed',
        'by_fixed',
        'by_percent',
        'buy_x_get_y'
    ];

    /**
     * "Apply for all orders
     */
    const ALL_ORDER = 1;

    /**
     * Only apply for the second order
     */
    const SECOND_ORDER = 0;

    /**
     * Custom amount for each order time
     */
    const EACH_ORDER = 2;

    /**
     * @var array
     */
    protected $courseData = [];

    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $courseFactory;

    /**
     * @var \Magento\Framework\DataObject
     */
    protected $dataObj;

    /**
     * @var Order\Simulator
     */
    protected $simulator;

    /**
     * Order constructor.
     * @param Context $context
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
     * @param Order\Simulator $simulator
     * @param \Magento\Framework\DataObject $dataObj
     */
    public function __construct(
        Context $context,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Riki\Subscription\Helper\Order\Simulator $simulator,
        \Magento\Framework\DataObject $dataObj
    ) {
        parent::__construct($context);
        $this->courseFactory = $courseFactory;
        $this->dataObj = $dataObj;
        $this->simulator = $simulator;
    }

    /**
     * @param $order
     * @param $subCourse
     * @param $profile
     * @return array
     */
    public function validateAmountRestriction($order, $subCourse, $profile)
    {
        $result = ['status' => true, 'message' => ''];
        if ($this->isHanpukaiSubscription($subCourse)) {
            return $result;
        }

        list ($min, $max, $option) =  $this->getMinMaxOption($subCourse, $profile);
        if ($option >= 0 && (!empty($min) || !empty($max))) {
            $orderPrice = $this->getOrderPrice($order);

            if (!empty($min) && $min > $orderPrice) {
                $result['status'] = false;
                $result['message'] = $this->getMinThresholdErrorMessage($min);
                $result['min'] = $min;
                return $result;
            }

            // Quote was inject from 1st order FO
            if ($order instanceof \Magento\Quote\Model\Quote) {
                if (!empty($max) && $max < $orderPrice) {
                    $result['status'] = false;
                    $result['message'] = $this->getMaxThresholdErrorMessage($max);
                    return $result;
                }
            }
        }
        return $result;
    }

    /**
     * @param $subCourse
     * @param $profile
     * @return array
     */
    public function validateSimulateOrderAmountRestriction($subCourse, $profile)
    {
        $result = ['status' => true, 'message' => ''];
        if ($this->isHanpukaiSubscription($subCourse)) {
            return $result;
        }

        list ($min, $max, $option) = $this->getMinMaxOption($subCourse, $profile);
        $productCart = $profile->getData('product_cart');

        /**
         * Client click button delete all product at page subscription .Only validate min amount if it has
         */
        if (empty($productCart) && !empty($min)) {
            $result['status'] = false;
            $result['message'] = $this->getMinThresholdErrorMessage($min);
            return $result;
        }

        $simulatorOrder = $this->simulator->createSimulatorOrderHasData($profile);
        if ($simulatorOrder && $productCart) {
            if ($option >= 0 && !empty($min)) {
                $orderPrice = $this->getOrderPrice($simulatorOrder);
                if (!empty($min) && $min > $orderPrice) {
                    $result['status'] = false;
                    $result['message'] = $this->getMinThresholdErrorMessage($min);
                }
            }
        }
        return $result;
    }

    /**
     * @param $profile
     * @return bool
     */
    private function is2ndOrder($profile)
    {
        if ($profile->getOrderTimes() == 1) {
            return true;
        }
        return false;
    }

    /**
     * @param $subCourse
     * @param $profile
     * @return array
     */
    public function getMinMaxOption($subCourse, $profile)
    {
        $data = [
            'min' => '',
            'max' => '',
            'option' => ''
        ];

        if ($profile) {
            $orderTime = $profile->getOrderTimes() + 1;
            $condition = json_decode($subCourse->getData('oar_condition_serialized'), true);
            if (!empty($condition)) {
                $minOption = isset($condition['minimum']) ? $condition['minimum'] : null;
                $maxOption = isset($condition['maximum']) ? $condition['maximum'] : null;
                $data['option'] = isset($minOption['option']) ? $minOption['option'] : '';

                // Only check maximum mount limitation for 1st order
                if($orderTime == 1 && !!$maxOption['amount']) {
                    $data['max'] = $maxOption['amount'];
                }

                /**
                 * if order time is not second order and option == Only apply for the second order
                 */
                if ($data['option'] == self::SECOND_ORDER && !$this->is2ndOrder($profile)) {
                    return [$data['min'], $data['max'], $data['option']];
                }

                $data['min'] = $this->getMinAmount($minOption, $orderTime, $data['option']);

            }
        }
        return [$data['min'], $data['max'], $data['option']];
    }

    /**
     * Load course factory
     *
     * @param $courseId
     * @return mixed
     */
    public function loadCourse($courseId)
    {
        if (isset($this->courseData[$courseId])) {
            return $this->courseData[$courseId];
        }
        $courseModel = $this->courseFactory->create()->load($courseId);
        $this->courseData[$courseId] = $courseModel;
        return $this->courseData[$courseId];
    }

    /**
     * @param $productCartData
     * @return array
     */
    public function cloneProductCartData($productCartData)
    {
        $returnData = [];
        foreach ($productCartData as $cartId => $productObj) {
            $returnData[$cartId] = clone $productObj;
        }
        return $returnData;
    }

    /**
     * @param $subCourse
     * @return bool
     */
    private function isHanpukaiSubscription($subCourse)
    {
        if ($subCourse->getData('subscription_type') == SubCourseType::TYPE_HANPUKAI) {
            return true;
        }
        return false;
    }

    /**
     * @param $order
     * @return mixed
     */
    private function getOrderPrice($order)
    {
        $orderPrice = $order->getGrandtotal() + (int) $order->getData('used_point_amount');
        $orderPriceFirstOrder = $order->getData('grand_total_first_order');
        if ($orderPriceFirstOrder) {
            return $orderPriceFirstOrder;
        }
        return $orderPrice;
    }

    /**
     * @param $minAmount
     * @return \Magento\Framework\Phrase
     */
    private function getMinThresholdErrorMessage($minAmount)
    {
        $message = 'The product is not allow to delete ';
        $message .= 'due to it\'s below total amount threshold %1 yen,';
        $message .= ' please add another product in advance if you want change';
        return __($message, $minAmount);
    }

    /**
     * @param $maxAmount
     * @return \Magento\Framework\Phrase
     */
    private function getMaxThresholdErrorMessage($maxAmount)
    {
        $message = 'The product is not allow to change ';
        $message .= 'due to it\'s obove total amount threshold %1 yen,';
        $message .= ' please remove another product in advance if you want change';
        return __($message, $maxAmount);
    }

    /**
     * Min amount
     * @param $minOption
     * @param $orderTime
     * @param $option
     * @return int|string
     */
    public function getMinAmount($minOption, $orderTime, $option)
    {
        $minAmount = '';
        $amounts = isset($minOption['amounts']) ? $minOption['amounts'] : null;

        switch ($option) {
            case self::ALL_ORDER:
                $minAmount = isset($minOption['amount']) ? $minOption['amount'] : null;
                break;
            case self::SECOND_ORDER:
                $minAmount = isset($minOption['amount']) ? $minOption['amount'] : null;
                break;
            case self::EACH_ORDER:
                if (!empty($amounts)) {
                    foreach ($amounts as $item) {
                        $orderFrom = (isset($item['from_order_time'])) ? $item['from_order_time'] : null;
                        $orderTo = (isset($item['to_order_time'])) ? $item['to_order_time'] : null;

                        // When To is empty, it means not limited for TO.
                        if ($orderFrom && empty($orderTo) && $orderTime >= $orderFrom) {
                            $minAmount = $item['amount'];
                            break;
                        } elseif ($orderFrom && $orderTo && $orderTime >= $orderFrom && $orderTime <= $orderTo) {
                            $minAmount = $item['amount'];
                            break;
                        }
                    }
                }
                break;
        }

        return $minAmount;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return array|null
     */
    public function validateAmountFirstOrderSimulator($quote)
    {
        $result = null;
        $profile = $this->dataObj;
        if ($quote && $quote instanceof \Magento\Quote\Model\Quote) {
            $subCourseId = $quote->getData('riki_course_id');
            /**
             * Only validate max min if it has data
             */
            if (!empty($quote->getAllItems()) && $subCourseId && $subCourseId > 0) {
                $totals = $quote->getTotals();
                if (isset($totals['subtotal'])) {
                    $subTotal = $totals['subtotal'];
                    $quote->setData('grand_total_first_order', $subTotal->getData('value_incl_tax'));
                }

                $subCourse = $this->loadCourse($subCourseId);
                if ($subCourse) {
                    $result = $this->validateAmountRestriction($quote, $subCourse, $profile);
                }
            }
        }
        return $result;
    }

    /**
     * Validate minimum amount restriction
     *
     * @param $order
     * @param $subCourse
     * @param $profile
     * @return array
     */
    public function validateMinimumAmountRestriction($order, $subCourse, $profile)
    {
        $result = ['status' => true, 'message' => ''];
        $message = 'Your profile amount is under %1 threshold so you cannot make the change, please add more product';

        list ($min, $max, $option) =  $this->getMinMaxOption($subCourse, $profile);
        if ($option >= 0 && (!empty($min) || !empty($max))) {
            $orderPrice = $this->getOrderPrice($order);

            if (!empty($min) && $min > $orderPrice) {
                $result['status'] = false;
                $result['message'] = __($message, $min);
                return $result;
            }
        }
        return $result;
    }
}
