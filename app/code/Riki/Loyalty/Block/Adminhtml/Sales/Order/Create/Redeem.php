<?php

namespace Riki\Loyalty\Block\Adminhtml\Sales\Order\Create;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Riki\Loyalty\Model\RewardQuote;
use Magento\Framework\Exception\InputException;

class Redeem extends \Magento\Sales\Block\Adminhtml\Order\Create\AbstractCreate
{
    /**
     * @var string
     */
    protected $_template = 'sales/order/create/redeem.phtml';

    /**
     * @var \Riki\Loyalty\Model\RewardQuoteFactory
     */
    protected $rewardQuoteFactory;

    /**
     * @var \Riki\Loyalty\Model\RewardManagement
     */
    protected $rewardManagement;

    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $courseFactory;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $cartRepositoryInterface;

    /**
     * Redeem constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Model\Session\Quote $sessionQuote
     * @param \Magento\Sales\Model\AdminOrder\Create $orderCreate
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Riki\Loyalty\Model\RewardManagement $rewardManagement
     * @param \Riki\Loyalty\Model\RewardQuoteFactory $rewardQuoteFactory
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\Sales\Model\AdminOrder\Create $orderCreate,
        PriceCurrencyInterface $priceCurrency,
        \Riki\Loyalty\Model\RewardManagement $rewardManagement,
        \Riki\Loyalty\Model\RewardQuoteFactory $rewardQuoteFactory,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepositoryInterface,
        array $data = []
    ) {
        parent::__construct($context, $sessionQuote, $orderCreate, $priceCurrency, $data);
        $this->rewardManagement = $rewardManagement;
        $this->rewardQuoteFactory = $rewardQuoteFactory;
        $this->courseFactory = $courseFactory;
        $this->cartRepositoryInterface = $cartRepositoryInterface;
    }

    /**
     * Get customer code from quote
     *
     * @return bool|string
     */
    public function getCustomerCode()
    {
        $customer = $this->getQuote()->getCustomer();
        if (!$customer) {
            return false;
        }
        $attribute = $customer->getCustomAttribute('consumer_db_id');
        if (!$attribute) {
            return false;
        }
        return $attribute->getValue();
    }

    /**
     * Prepare data for this block
     *
     * @return $this
     */
    private function _initRewardPoint()
    {
        $quote = $this->getQuote();
        if (!$quote->getId() || !$quote->getItemsCount()) {
            $this->setData('show_reward_point', false);
            return $this;
        }
        $rewardQuote = $this->rewardQuoteFactory->create()->load($quote->getId(), RewardQuote::QUOTE_ID);
        if ($rewardQuote->getId()) {
            $this->setData('reward_user_setting', $rewardQuote->getRewardUserSetting());
            $this->setData('use_point_amount', $rewardQuote->getRewardUserRedeem());
        }
        return $this;
    }

    /**
     * Get customer point balance
     *
     * @return int
     */
    public function getPointBalance()
    {
        if (!$this->hasData('point_balance')) {
            $customerCode = $this->getCustomerCode();
            $balance = $this->rewardManagement->getPointBalance($customerCode);
            $this->setData('point_balance', $balance);
        }
        return $this->getData('point_balance');
    }

    /**
     * @return float
     */
    public function getPointBalanceFormatted()
    {
        return $this->priceCurrency->format($this->getPointBalance(), false);
    }
    
    /**
     * Redeem options
     *
     * @return array
     */
    public function getPointUseOptions()
    {
        return [
            RewardQuote::USER_DO_NOT_USE_POINT => __('Do not use a point'),
            RewardQuote::USER_USE_ALL_POINT => __('Use all point'),
            RewardQuote::USER_USE_SPECIFIED_POINT => __('Using some of point')
        ];
    }

    /**
     * Get shopping point setting: redeem type
     *
     * @return integer
     */
    public function getRewardUserSetting()
    {
        if (!$this->hasData('reward_user_setting')) {
            $setting = $this->rewardManagement->getRewardUserSetting($this->getCustomerCode());
            $this->setData('reward_user_setting', $setting['use_point_type']);
            $this->setData('use_point_amount', $setting['use_point_amount']);
        }
        return $this->getData('reward_user_setting');
    }

    /**
     * Get shopping point setting: redeem amount
     *
     * @return integer
     */
    public function getRewardUserRedeem()
    {
        $redeem = $this->getData('use_point_amount');
        return min($redeem, $this->getPointBalance());
    }

    /**
     * @return float
     */
    public function getCartTotal()
    {
        $quote = $this->getQuote();
        if (!$quote->getId()) {
            return 0.0000;
        }
        return $quote->getBaseGrandTotal() + (float) $quote->getData('base_used_point_amount');
    }

    /**
     * @return float
     */
    public function getCartTotalFormatted()
    {
        return $this->priceCurrency->format($this->getCartTotalValue(), false);
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function hasPointForTrial()
    {
        $course = $this->getCourse();
        if ($course != false && $course->getPointForTrial()) {
            return true;
        }
        return false;
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getShoppingPointTrial()
    {
        return $this->getQuote()->getUsedPoint();
    }

    /**
     * @return RewardQuote|false
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCourse()
    {
        $courseId = $this->getQuote()->getRikiCourseId();
        if ($courseId) {
            /** @var \Riki\Loyalty\Model\RewardQuote $courseModel */
            $courseModel = $this->courseFactory->create()->load($courseId);
            return $courseModel;
        } else {
            return false;
        }
    }

    /**
     * Do not show this block if this customer does not have point balance
     * @return string
     * @throws InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _toHtml()
    {
        $this->_initUsePoint();

        $this->_initRewardPoint();

        if ($this->hasPointForTrial()) {
            $this->setTemplate('Riki_Loyalty::sales/order/create/point_trial.phtml');
            return parent::_toHtml();
        }

        if (!$this->getPointBalance() || $this->getData('show_reward_point') === false) {
            return '';
        }

        return parent::_toHtml(); // TODO: Change the autogenerated stub
    }

    /**
     * @throws InputException
     */
    protected function _initUsePoint()
    {
        $isUseRewardPoint = $this->_request->getParam('reload_reward_point');
        $usePointAmount = $this->_request->getParam('used_points');
        $cartId = $this->_request->getParam('cart_id');
        if ($isUseRewardPoint) {
            $quote = $this->getCurrentQuote();
            if ($quote->getId() == $cartId) {
                switch ((int)$this->_request->getParam('option')) {
                    case RewardQuote::USER_DO_NOT_USE_POINT:
                        $option = \Riki\Loyalty\Model\RewardQuote::USER_DO_NOT_USE_POINT;
                        $this->applyRewardPointBackend($quote, 0, $option);
                        break;
                    case RewardQuote::USER_USE_ALL_POINT:
                        $option = \Riki\Loyalty\Model\RewardQuote::USER_USE_ALL_POINT;
                        $this->applyRewardPointBackend($quote, 0, $option);
                        break;
                    case RewardQuote::USER_USE_SPECIFIED_POINT:
                        $option = \Riki\Loyalty\Model\RewardQuote::USER_USE_SPECIFIED_POINT;
                        $this->applyRewardPointBackend($quote, $usePointAmount, $option);
                        break;
                }
            }
        }
    }

    /**
     * @return \Magento\Quote\Model\Quote
     * @throws InputException
     */
    protected function getCurrentQuote()
    {
        $quote = $this->getQuote();
        if (!$quote->getId()) {
            throw new InputException(__('This quote is no longer exist'));
        }
        return $quote;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param $usedPoints
     * @param $option
     * @throws \Exception
     */
    protected function applyRewardPointBackend(\Magento\Quote\Model\Quote $quote, $usedPoints, $option)
    {
        if ($quote) {
            $cartId = $quote->getId();
            /*RMM-375 applies point for trial*/
            if ($quote->getData('point_for_trial') > 0) {
                $usedPoints = $quote->getData('point_for_trial');
                $option = 1;
            }
            /** @var \Riki\Loyalty\Model\RewardQuote $rewardQuote */
            $rewardQuote = $this->rewardQuoteFactory->create();
            $rewardQuote->load($quote->getId(), 'quote_id');
            $rewardQuote->setQuoteId($cartId);
            $rewardQuote->setRewardUserSetting($option);
            $rewardQuote->setRewardUserRedeem($usedPoints);
            $rewardQuote->save();
            $quote->collectTotals();
            $this->cartRepositoryInterface->save($quote);
        }
    }
}
