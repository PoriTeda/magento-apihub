<?php

namespace Riki\Theme\Block\Html\Header;

use Riki\SubscriptionMembership\Model\Customer\Attribute\Source\Membership;
use Riki\Customer\Model\Config\Source\ShoshaCode;

class Menu extends \Magento\Framework\View\Element\Template
{
    const SHOSHA_CODE = 'CEDYNA';

    /**
     * @var string
     */
    protected $_template = 'html/header_custom.phtml';

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    /**
     * @var \Riki\Customer\Helper\Membership
     */
    protected $membership;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSessionFactory;

    /**
     * @var bool
     */
    protected $shoShaCode = false;

    /**
     * @var array
     */
    protected $memberShip = [];

    /**
     * @var \Riki\Customer\Model\ShoshaFactory
     */
    protected $shoshaFactory;

    /**
     * Menu constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Riki\Customer\Helper\Membership $membership
     * @param \Magento\Customer\Model\SessionFactory $sessionFactory
     * @param \Riki\Customer\Model\ShoshaFactory $shoshaFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\Http\Context $httpContext,
        \Riki\Customer\Helper\Membership $membership,
        \Magento\Customer\Model\SessionFactory $sessionFactory,
        \Riki\Customer\Model\ShoshaFactory $shoshaFactory,
        array $data = []
    ) {
        $this->httpContext = $httpContext;
        $this->membership = $membership;
        $this->customerSessionFactory = $sessionFactory;
        $this->shoshaFactory = $shoshaFactory;
        parent::__construct($context, $data);
    }

    /**
     * @return boolean
     */
    public function isLoggedIn()
    {
        $customerFactory  = $this->customerSessionFactory->create();
        if ($customerFactory->getCustomerId() != null) {
            $customer = $customerFactory->getCustomer();
            if ($customer) {
                $membership =  explode(',', $customer->getMembership());
                if (!empty($membership)) {
                    $this->memberShip = array_flip($membership);
                }

                $shoShaBusinessCode = $customer->getShoshaBusinessCode();
                if ($shoShaBusinessCode) {
                    $this->shoShaCode = $this->getShoShaCode($shoShaBusinessCode);
                }
            }
        }

        return $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
    }

    /**
     * @return bool
     */
    public function canShoShaCodeCEDYNA()
    {
        if ($this->shoShaCode) {
            return true;
        }
        return false;
    }

    /**
     * Check customer is invoice
     *
     * @return bool
     */
    public function isCustomerInvoice()
    {
        //invoice
        if (isset($this->memberShip[Membership::CODE_4])) {
            return true;
        }
        return false;
    }

    /**
     * @return array
     */
    public function getWebsiteCode()
    {
        return $this->membership->getWebsiteCode();
    }

    /**
     * Get home link
     *
     * @return mixed|string
     */
    public function getHomeUrl()
    {
        $link =  $this->getDataConfig('general/home_link/link');
        return $link;
    }

    /**
     * Get link to Guide & Rules
     *
     * @return mixed|string
     */
    public function getLinkGuideRules()
    {
        if ($this->shoShaCode) {
            return $this->getDataConfig('general/link_to_guid_and_rules/has_shosha_code');
        } else {
            return $this->getDataConfig('general/link_to_guid_and_rules/default');
        }
    }

    /**
     * Get link to compliance information
     *
     * @return mixed|string
     */
    public function getLinkComplianceInformation()
    {
        if ($this->shoShaCode) {
            return $this->getDataConfig('general/link_to_compliance_information/has_shosha_code');
        } else {
            return $this->getDataConfig('general/link_to_compliance_information/default');
        }
    }

    /**
     * Get link to compliance information
     *
     * @return mixed|string
     */
    public function getLinkFAQ()
    {
        if ($this->shoShaCode) {
            return $this->getDataConfig('general/link_to_faq/has_shosha_code');
        } else {
            return $this->getDataConfig('general/link_to_faq/default');
        }
    }

    /**
     * Get link address to note function
     *
     * @return mixed|string
     */
    public function getLinkAddressNoteFunction()
    {
        return $this->getDataConfig('general/link_to_address_note_function/default');
    }

    /**
     * Get link to enquiry
     *
     * @return mixed|string
     */
    public function getLinkToEnquiry()
    {
        return $this->getDataConfig('general/link_to_inquiry/default');
    }

    /**
     * Check membership cis,cnc
     *
     * @return bool
     */
    public function isMembershipCncOrCis()
    {
        if (!empty($this->memberShip)) {
            //CNC
            if (isset($this->memberShip[Membership::CODE_5])) {
                return true;
            }

            //cis
            if (isset($this->memberShip[Membership::CODE_6])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get shosha code
     *
     * @param $shoshaBusinessCode
     * @return bool
     */
    public function getShoShaCode($shoShaBusinessCode)
    {
        if ($shoShaBusinessCode) {
            $shoshaCollection = $this->shoshaFactory->create()->getCollection()
                ->addFieldToSelect('shosha_code')
                ->addFieldToFilter('shosha_business_code', $shoShaBusinessCode)
                ->setPageSize(1);

            if ($shoshaCollection->getSize()) {
                foreach ($shoshaCollection as $shosha) {
                    if (ShoshaCode::CEDYNA == $shosha->getData('shosha_code')) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * @param $path
     * @return mixed|string
     */
    public function getDataConfig($path)
    {
        $websiteCode = $this->getWebsiteCode();
        $configValue = $this->_scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        );

        return $configValue ;
    }
}
