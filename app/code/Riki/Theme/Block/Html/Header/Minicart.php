<?php

namespace Riki\Theme\Block\Html\Header;

use  Riki\GiftWrapping\Model\ConfigProvider;

/**
 * Logo page header block
 *
 * @api
 * @since 100.0.2
 */
class Minicart extends \Magento\Framework\View\Element\Template
{

    /**
     * @var ConfigProvider
     */
    protected $giftWrapping;
    /**
     * @var \Magento\Checkout\Block\Cart\Sidebar
     */
    private $sidebar;
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $checkoutSession;


    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $_courseFactory;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlInterface;

    /**
     * Minicart constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param ConfigProvider $giftWrapping
     * @param \Magento\Checkout\Block\Cart\Sidebar $sidebar
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
     * @param \Magento\Framework\UrlInterface $urlInterface
     * @param array $data
     * @param \Magento\Framework\Serialize\Serializer\Json|null $serializer
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Riki\GiftWrapping\Model\ConfigProvider $giftWrapping,
        \Magento\Checkout\Block\Cart\Sidebar $sidebar,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Magento\Framework\UrlInterface $urlInterface,
        array $data = [],
        \Magento\Framework\Serialize\Serializer\Json $serializer = null
    )
    {
        $this->giftWrapping = $giftWrapping;
        $this->sidebar = $sidebar;
        $this->checkoutSession = $checkoutSession;
        $this->_courseFactory = $courseFactory;
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Serialize\Serializer\Json::class);
        $this->urlInterface     = $urlInterface;
        parent::__construct($context, $data);
    }

    /**
     * @return \Magento\Framework\DataObject
     */
    public function getDesignsInfo()
    {
        return new \Magento\Framework\DataObject($this->giftWrapping->getDesignsInfoMinicCart());
    }

    /**
     * @return string
     */
    public function getCheckoutConfig()
    {
        return $this->serializer->serialize([
        'spotAddItem' => $this->getUrl('riki-checkout/sidebar/spotAddItem', ['_secure' => $this->getRequest()->isSecure()])
        ,'updateItemQtyCustomUrl' => $this->getUrl('riki-checkout/sidebar/updateItemQtyCustom', ['_secure' => $this->getRequest()->isSecure()])
        ,'updateGiftWrapping' => $this->getUrl('multicheckout/update/wrapping', ['_secure' => $this->getRequest()->isSecure()])
        ,'removeItemUrl' => $this->getUrl('checkout/sidebar/removeItem', ['_secure' => $this->getRequest()->isSecure()])]);
    }

    /**
     * Check subscription is hanpukai subscription or not
     *
     */

    public function isHanpukaiSubscription()
    {
        $courseCode = null;

        $currentUrl = $this->urlInterface->getCurrentUrl();
        if (strpos($currentUrl, 'subscription/hanpukai/view') !== false) {
            $courseCode = $this->getRequest()->getParam('code');
        }
        if (strpos($currentUrl, '/subscription-page/view') !== false) {
            $courseCode = $this->getRequest()->getParam('id');
        }


        if ($courseCode != null) {
            $courseModel = $this->_courseFactory->create()->getCollection()
                ->addFieldToFilter('course_code', $courseCode)
                ->addFieldToSelect(['course_id','subscription_type'])->getData();
            if (count($courseModel) > 0) {
                if ($courseModel[0]['subscription_type'] == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI) {
                    return true;
                }
            }
            return false;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isRealCart()
    {
        $quote = $this->checkoutSession->getQuote();
        if (empty($quote->getId())) {
            return false;
        }
        /* @var $quote \Magento\Quote\Model\Quote */
        if($quote && $quote instanceof \Magento\Quote\Model\Quote) {
            if (count($quote->getAllVisibleItems()) == 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return string
     */
    public function urlCheckoutCartPage() {
        return $this->urlInterface->getUrl('checkout/cart');
    }
}
