<?php
namespace Riki\SubscriptionProfileDisengagement\Plugin\SubscriptionCourse\Helper;

class Data
{
    /** @var \Riki\SubscriptionProfileDisengagement\Helper\Data  */
    protected $hepper;

    /** @var \Magento\Framework\App\RequestInterface  */
    protected $request;

    /**
     * Data constructor.
     * @param \Riki\SubscriptionProfileDisengagement\Helper\Data $helper
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Riki\SubscriptionProfileDisengagement\Helper\Data $helper,
        \Magento\Framework\App\RequestInterface $request
    ){
        $this->hepper = $helper;
        $this->request = $request;
    }

    /**
     * @param \Riki\SubscriptionCourse\Helper\Data $subject
     * @param \Closure $proceed
     * @param $arrProductIdInCart
     * @param $courseId
     * @param null $nDelivery
     * @return int
     */
    public function aroundCheckCartIsValidForCourse(
        \Riki\SubscriptionCourse\Helper\Data $subject,
        \Closure $proceed,
        $arrProductIdInCart,
        $courseId,
        $nDelivery = null
    ) {

        $profileId = $this->request->getParam('profile_id');

        if ($profileId && $this->hepper->isPendingToDisengage($profileId)) {
            return 0;
        }

        return $proceed($arrProductIdInCart, $courseId, $nDelivery);
    }

    /**
     * skip validate product for disengaged profile
     *
     * @param \Riki\SubscriptionCourse\Helper\Data $subject
     * @param \Closure $proceed
     * @param $courseId
     * @param $arrProductId
     * @param $qty
     * @param $arrProductIdQty
     * @param null $nDelivery
     * @return int
     */
    public function aroundValidateProductOfCourse(
        \Riki\SubscriptionCourse\Helper\Data $subject,
        \Closure $proceed,
        $courseId,
        $arrProductId,
        $qty,
        $arrProductIdQty,
        $nDelivery = null
    ) {

        $profileId = $this->request->getParam('profile_id');

        if ($profileId && $this->hepper->isPendingToDisengage($profileId)) {
            return 0;
        }

        return $proceed($courseId, $arrProductId, $qty, $arrProductIdQty, $nDelivery);
    }
}
