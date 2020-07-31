<?php

namespace Riki\TagManagement\Block;

class OnePageSuccess extends \Magento\Framework\View\Element\Template
{
    const FREQUENCY_UNIT = 'month';
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $_customerModel;
    /**
     * @var \Riki\TagManagement\Helper\Helper
     */
    protected $helper;
    /**
     * @var \Magento\Sales\Api\OrderAddressRepositoryInterface|\Magento\Sales\Model\Order\Address
     */
    protected $_salesOrderAddressModel;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $profileFactory;
    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    protected $_cookieManager;
    /**
     * OnePageSuccess constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Riki\TagManagement\Helper\Helper $helper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Api\OrderAddressRepositoryInterface $addressRepository
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Riki\TagManagement\Helper\Helper $helper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Api\OrderAddressRepositoryInterface $addressRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\Session $customerSession,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        array $data = []
    )
    {
        $this->helper = $helper;
        $this->_checkoutSession = $checkoutSession;
        $this->_salesOrderAddressModel = $addressRepository;
        $this->_customerModel = $customerRepository;
        $this->customerSession = $customerSession;
        $this->profileFactory = $profileFactory;
        $this->_cookieManager = $cookieManager;
        parent::__construct($context, $data);
    }

    /**
     * @return $this
     */
    public function _prepareLayout()
    {
        return parent::_prepareLayout();
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
       
        if (false != $this->getTemplate()) {
            return parent::_toHtml();
        };
        $html = '';
        $order = $this->_checkoutSession->getLastRealOrder();
        //order have gift wrapping
        $html .= $this->helper->getConfigTagOrderComplete($order);
        return $html;
    }
}