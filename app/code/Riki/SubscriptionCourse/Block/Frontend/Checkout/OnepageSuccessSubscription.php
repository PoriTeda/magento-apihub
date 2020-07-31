<?php

namespace Riki\SubscriptionCourse\Block\Frontend\Checkout;

/**
 * OnepageSuccessSubscription on Order success page
 */
class OnepageSuccessSubscription extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Cms\Model\BlockRepository
     */
    protected $staticBlockRepository;

    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $subProfileModel;

    /**
     * @var \Riki\SubscriptionCourse\Helper\Data
     */
    protected $courseHelper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Cms\Model\BlockRepository $staticBlockRepository
     * @param \Riki\Subscription\Model\Profile\ProfileFactory $subProfileModel
     * @param \Riki\SubscriptionCourse\Helper\Data $courseHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Cms\Model\BlockRepository $staticBlockRepository,
        \Riki\Subscription\Model\Profile\ProfileFactory $subProfileModel,
        \Riki\SubscriptionCourse\Helper\Data $courseHelper,
        array $data = []
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->staticBlockRepository = $staticBlockRepository;
        $this->subProfileModel = $subProfileModel;
        $this->courseHelper = $courseHelper;
        parent::__construct($context, $data);
    }

    /**
     * Get static block
     *
     * @param string $blockIdentifier
     * @return \Magento\Cms\Model\Block|bool
     */
    public function getStaticBlock($blockIdentifier)
    {
        try {
            return $this->staticBlockRepository->getById($blockIdentifier);
        } catch (\Exception $e) {
            // Write log.
        }
        return false;
    }

    /**
     * Get order in success page
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $order = $this->checkoutSession->getLastRealOrder();
    }

    /**
     * Get subscription course by order
     *
     * @return mixed|string
     */
    public function getSubscriptionCourse()
    {
        $order = $this->getOrder();
        if ($order->getData('riki_type') != \Riki\SubscriptionCourse\Model\Course\Type::TYPE_ORDER_SPOT) {
            $profile = $this->subProfileModel->create()->load($order->getData('subscription_profile_id'));
            if ($profile->getCourseId()) {
                if ($subCourse = $this->courseHelper->loadCourse($profile->getCourseId())) {
                    return $subCourse;
                }
            }
        }

        return '';
    }

    /**
     * Is subscription + hanpukai and specific course code in block 'order_success_subscription' setting
     *
     * @return boolean
     */
    public function isSubscriptionAndSpecificCourseCode()
    {
        $subCourse = $this->getSubscriptionCourse();
        if ($subCourse) {
            $courseCode = $subCourse->getData('course_code');

            // Get list course code in block 'order_success_subscription' setting.
            $blockOrderSuccessSub = $this->getStaticBlock('order_success_subscription');
            if ($blockOrderSuccessSub && $blockOrderSuccessSub->isActive()) {
                $listCourseCode = array_map(
                    'trim',
                    explode(',', $blockOrderSuccessSub->getData('course_code'))
                );

                if (in_array($courseCode, $listCourseCode)) {
                    return true;
                }
            }
        }

        return false;
    }
}
