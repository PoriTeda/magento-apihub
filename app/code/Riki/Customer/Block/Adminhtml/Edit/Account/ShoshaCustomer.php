<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Customer\Block\Adminhtml\Edit\Account;

class ShoshaCustomer extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_dateTime;
    /**
     * @var \Magento\Framework\AuthorizationInterface
     */
    protected $_auth;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface $_localResolver
     */
    protected $_localResolver;

    /**
     * ShoshaCustomer constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\AuthorizationInterface $auth
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\AuthorizationInterface $auth,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_dateTime = $dateTime;
        $this->_auth = $auth;
        $this->_localResolver = $localeResolver;
    }

    /**
     *
     * @return int
     */
    public function checkAuthorizeCreateShoshaCustomer(){

        if($this->_auth->isAllowed('Riki_Customer::create_shosha_customer'))
        {
            return 1;
        }

        return 0;

    }

    /**
     * Get current date for js calculate approval restriction
     * @return String $currentDate
     */
    public function getCurrentDate(){
       return $this->_dateTime->date('m/d/Y');
    }

    /**
     * Get status controller edit or create new
     * @return integer $isEdit
     */
    public function isEdit(){
        $isEdit = 0;
        $currentUrl = $this->_urlBuilder->getCurrentUrl();
        if(strpos($currentUrl,'customer/index/edit') !== false){
            $isEdit = 1;
        }
        return $isEdit;
    }
    
    public function getAreaForCreateCustomer()
    {
        $currenLocale =  $this->_localResolver->getLocale();
        return $currenLocale;
    }

}
