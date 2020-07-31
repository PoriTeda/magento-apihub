<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Customer\Plugin\Block\Address;

/**
 * Onepage checkout block
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */

class OnepageChange
{
    const ID_AMBASSADOR = 3 ;
    protected $_customerSession;

    protected $_dataHelper;
    /**
     * @var \Magento\Customer\Helper\Session\CurrentCustomer
     */
    protected $currentCustomer;
    

    /**
     * OnepageChange constructor.
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Riki\Customer\Helper\Data $dataHelper
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Riki\Customer\Helper\Data $dataHelper,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer
    ){
        $this->_customerSession = $customerSession;
        $this->_dataHelper = $dataHelper;
        $this->currentCustomer = $currentCustomer;
    }

    /**
     * @return string
     */
    public function afterGetJsLayout(\Magento\Checkout\Block\Onepage $subject, $layoutProcessors )
    {
        $arrayMember = array();
        $customerMembership = $this->currentCustomer->getCustomer()->getCustomAttribute('membership')->getValue() ;

        if($customerMembership){
            $arrayMember = explode(',',$customerMembership);
        }
        $layout = \Zend_Json::decode($layoutProcessors);

        if(!in_array(self::ID_AMBASSADOR, $arrayMember)){
            unset($layout['components']['checkout']['children']['steps']['children']['shipping-step']
                ['children']['shippingAddress']['children']['shipping-address-fieldset']['children']
                ['riki_type_address']);
        }
        return \Zend_Json::encode($layout);
    }

 
}
