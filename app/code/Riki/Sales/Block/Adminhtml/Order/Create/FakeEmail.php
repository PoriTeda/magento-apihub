<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Sales\Block\Adminhtml\Order\Create;

/**
 * Adminhtml sales order create block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class FakeEmail extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $_customerRepository;

    /**
     * @var \Riki\Sales\Helper\Data
     */
    protected $_orderHelper;

    /**
     * FakeEmail constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Riki\Sales\Helper\Data $orderHelper
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = [],
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Riki\Sales\Helper\Data $orderHelper
    )
    {
        parent::__construct($context, $data);
        $this->_customerRepository = $customerRepository;
        $this->_orderHelper = $orderHelper;
    }

    /**
     * GenerateEmail
     *
     * @return string
     */
    public function generateEmail(){
        while(true){

            $randomName = $this->makeRandomString(10);
            $randomDomainName = $this->_orderHelper->getOrderRandomDomain();
            $randomDomainName = ($randomDomainName != '')?$randomDomainName:'@example.com';
            $randomEmail = $randomName.$randomDomainName;
            try{
                $customer = $this->_customerRepository->get($randomEmail,$this->_storeManager->getWebsite()->getId());
            }catch (\Exception $e){
                $customer = NULL;
                $this->_logger->info($e->getMessage());
            }

            if(NULL == $customer){
                return $randomEmail;
            }
        }
    }

    /**
     * Make Random String
     *
     * @param int $max
     *
     * @return string
     */
    public function makeRandomString($max=6) {

        return \Zend\Math\Rand::getString($max, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', true);

    }

}
