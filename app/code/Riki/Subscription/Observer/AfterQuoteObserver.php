<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Subscription\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Riki\Subscription\Model\Constant;

/**
 * Whhen cart is empty. remove riki_course_id
 *
 * Class QuoteObserver
 * @package Riki\Subscription\Observer
 */
class AfterQuoteObserver implements ObserverInterface
{

    protected $objQuote;
    protected $_request;

    /**
     * @var \Riki\SubscriptionPage\Helper\Data
     */
    protected  $subPageHelperData;
    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $sessionQuote;
    /**
     * @var \Riki\Subscription\Helper\Data
     */
    protected $helperProfileData;
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileRepository
     */
    protected $profileRepository;
    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $courseFactory;
    /**
     * @var \Riki\Loyalty\Model\RewardQuoteFactory
     */
    protected $rewardQuoteFactory;
    /**
     * AfterQuoteObserver constructor.
     * @param \Riki\SubscriptionPage\Helper\Data $subHelperData
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Backend\Model\Session\Quote $sessionQuote
     * @param \Riki\Subscription\Helper\Data $helperProfileData
     * @param \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
     * @param \Riki\Loyalty\Model\RewardQuoteFactory $rewardQuoteFactory
     */
    public function __construct(
        \Riki\SubscriptionPage\Helper\Data $subHelperData,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Riki\Subscription\Helper\Data $helperProfileData,
        \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Riki\Loyalty\Model\RewardQuoteFactory $rewardQuoteFactory
    ){
        $this->subPageHelperData = $subHelperData;
        $this->_request = $request;
        $this->sessionQuote = $sessionQuote;
        $this->helperProfileData = $helperProfileData;
        $this->profileRepository = $profileRepository;
        $this->courseFactory = $courseFactory;
        $this->rewardQuoteFactory = $rewardQuoteFactory;
    }

    /**
     * Set persistent data into quote
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        /** @var $objQuote \Magento\Quote\Model\Quote */
        $objQuote = $observer->getEvent()->getQuote();
        if ($this->sessionQuote->getOrderId()) {
            if (!$objQuote->getRikiCourseId() and !$objQuote->getRikiFrequencyId()) {
                $order = $this->sessionQuote->getOrder();
                if ($order instanceof \Magento\Sales\Model\Order) {
                    $subscriptionInfo = $this->helperProfileData->getSubscriptionInfo($order);
                    if (sizeof($subscriptionInfo) > 0) {
                        $objQuote->setRikiFrequencyId($subscriptionInfo['frequency_id']);
                        $objQuote->setRikiCourseId($subscriptionInfo['course_id']);

                        /** RMM-380 add point trial for quote when edit order */
                        $this->addPointTrialToQuote($objQuote, $subscriptionInfo['course_id']);
                    }
                }
            }
        }else {
            try {
                if ($frequencyID = $this->_request->getParam('frequency_id', false)) {
                    $objQuote->setRikiFrequencyId($frequencyID);
                }

                if ($courseId = $this->_request->getParam('course_id', false)) {
                    $objQuote->setRikiCourseId($courseId);
                    if ($this->subPageHelperData->getSubscriptionType($courseId)
                        == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI) {
                        if ($this->_request->getParam('hanpukai_qty', false)) {
                            $hanpukaiQty = $this->_request->getParam('hanpukai_qty', false);
                            if ($objQuote->getData(Constant::RIKI_HANPUKAI_QTY)) {
                                $hanpukaiQty = $hanpukaiQty + $objQuote->getData(Constant::RIKI_HANPUKAI_QTY);
                            }
                            $objQuote->setData(Constant::RIKI_HANPUKAI_QTY, $hanpukaiQty);
                        }
                    }
                    /** RMM-380 add point trial for quote when create order in BO */
                    $this->addPointTrialToQuote($objQuote, $courseId);
                }
            } catch (\Exception $e) {
                throwException($e);
            }
        }
        $this->objQuote = $objQuote;
        if (!$objQuote) {
            return;
        }
        $this->_clearRIkiCourseIdWhenQuoteIsEmpty();
    }

    /**
     * @param $quote
     * @param $courseId
     * @throws \Exception
     */
    protected function addPointTrialToQuote($quote, $courseId)
    {
        if (!$quote->getData('point_for_trial')) {
            if ($pointForTrial = $this->getPointForTrial($courseId)) {
                $quote->setData('point_for_trial', $pointForTrial);

                /** set point trial for reward quote */
                $rewardQuote = $this->rewardQuoteFactory->create()
                    ->load($quote->getId(), \Riki\Loyalty\Model\RewardQuote::QUOTE_ID);

                if(!$rewardQuote->getQuoteId())
                {
                    $rewardQuote->setData(\Riki\Loyalty\Model\RewardQuote::QUOTE_ID, $quote->getId());
                }
                $rewardQuote->setData(
                    'reward_user_setting',
                    \Riki\Loyalty\Model\RewardQuote::USER_USE_ALL_POINT)
                    ->setData('reward_user_redeem', $pointForTrial);
                $rewardQuote->save();
            }
        }
    }
    /**
     * Clear riki_course_id when cart item is empty
     */
    private function _clearRIkiCourseIdWhenQuoteIsEmpty()
    {
        if($this->objQuote->isSaveAllowed() && (empty($this->objQuote->getAllItems()) && $this->objQuote->getData("items_qty") == 0)) {
            /**
             * @TODO: disable clear riki_cource_id when cart is empty
             */
            $this->objQuote->setData(Constant::QUOTE_RIKI_COURSE_ID, null);
            $this->objQuote->setData(Constant::RIKI_FREQUENCY_ID, null);
            $this->objQuote->setData(Constant::POINT_FOR_TRIAL, null);
        }
    }

    /**
     * @param int $courseId
     * @return bool|string
     */
    public function getPointForTrial($courseId)
    {
        /** @var \Riki\SubscriptionCourse\Model\Course $course */
        $course = $this->courseFactory->create()->load($courseId);
        if ($course->getPointForTrial()) {
            return $course->getPointForTrial();
        }
        return false;
    }
}
