<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Riki\Checkout\Block\Checkout\Cart;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Model\Context as CustomerContext;
use Riki\SubscriptionMembership\Model\Customer\Attribute\Source\Membership as CustomerMembership;

class BackToTopCart extends Template
{
    const ONLINE = 'back_to_top_cart/membership_online';
    const ONLINE_AMBASSADOR = 'back_to_top_cart/membership_online_ambassador';
    const ONLINE_WELLNESS_AMBASSADOR = 'back_to_top_cart/membership_online_wellness_ambassador';
    const ONLINE_WELLNESS_AMBASSADOR_AMBASSADOR = 'back_to_top_cart/membership_online_wellness_ambassador_ambassador';

    /**
     * @var \Magento\Customer\Model\SessionFactory
     */
    protected $customerSessionFactory;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Group\CollectionFactory
     */
    protected $groupCustomerFactory;
    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $eavConfig;
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * BackToTopCart constructor.
     * @param Context $context
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Magento\Customer\Model\SessionFactory $customerSessionFactory
     * @param \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $groupCustomerFactory
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Customer\Model\SessionFactory $customerSessionFactory,
        \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $groupCustomerFactory,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Checkout\Model\Session $checkoutSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->customerSessionFactory = $customerSessionFactory;
        $this->groupCustomerFactory = $groupCustomerFactory;
        $this->httpContext = $httpContext;
        $this->scopeConfig = $context->getScopeConfig();
        $this->eavConfig = $eavConfig;
        $this->customerRepository = $customerRepository;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Check customer login
     *
     * @return bool
     */
    public function isLogin()
    {
        if ($this->customerSessionFactory->create()->isLoggedIn()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get url link config
     *
     * @param $path
     * @return mixed
     */
    public function getUrlConfig($path)
    {
        $value = $this->scopeConfig->getValue('riki_config_back_to_top/' . $path);
        if ($value != null) {
            return $value;
        }
        return '#';
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getUrlBackToTopCart()
    {
        $customerFactory = $this->customerSessionFactory->create();
        $url = false;
        if ($customerFactory && $customerFactory->getCustomerId() != null) {
            $customer = $customerFactory->getCustomer();
            $url = $this->getUrlMemberShip($customer);
            if ($url) {
                return $url;
            }
        }
        return $url;
    }

    /**
     * @param $customer
     * @return mixed|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getUrlMemberShip($customer)
    {
        $customer = $this->customerRepository->getById($customer->getId());
        $customerMembershipRaw = $customer->getCustomAttribute('membership');
        $url = false;
        if ($customerMembershipRaw && $customerMembershipRaw->getValue() != '') {
            $membership = explode(',', $customerMembershipRaw->getValue());
            $online = (in_array(CustomerMembership::CODE_2, $membership)) ? true : false;
            $ambassador = (in_array(CustomerMembership::CODE_3, $membership)) ? true : false;
            $wellnessAmbassador = in_array(CustomerMembership::CODE_15, $membership) ? true : false;
            if (is_array($membership) && !empty($membership)) {
                if ($wellnessAmbassador || ($online && $wellnessAmbassador && $ambassador)) {
                    $url = $this->getUrlConfig(self::ONLINE_WELLNESS_AMBASSADOR_AMBASSADOR);
                } elseif ($online && $wellnessAmbassador) {
                    $url = $this->getUrlConfig(self::ONLINE_WELLNESS_AMBASSADOR);
                } elseif ($online && $ambassador) {
                    $url = $this->getUrlConfig(self::ONLINE_AMBASSADOR);
                } elseif ($online) {
                    $url = $this->getUrlConfig(self::ONLINE);
                }
            }
        }
        return $url;
    }

    /**
     * @return bool
     */
    public function isSpotOrder()
    {
        $quote = $this->checkoutSession->getQuote();
        if ($quote && !$quote->getData('riki_course_id')) {
            return true;
        }
        return false;
    }
}
