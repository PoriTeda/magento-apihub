<?php
namespace Riki\Customer\Observer;

class ValidateCustomer implements \Magento\Framework\Event\ObserverInterface
{
    const ITOCHU = 0;
    const MC = 1;
    const CEDYNA = 2;
    const FKJEN = 3;
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;

    /**
     * ValidateCustomer constructor.
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     */
    public function __construct(
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Riki\Customer\Model\ShoshaFactory $modelShoshaFactory
    )
    {
        $this->_customerFactory = $customerFactory;
        $this->_modelShoshaFactory = $modelShoshaFactory;
    }


    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $customer = $observer->getCustomer();

        $errors =  $observer->getTransport()->getErrors();
        $shoshaBusinessCode = $customer->getShoshaBusinessCode();
        if($customer && $shoshaBusinessCode != ''){

            $aShoshaCollections = $this->_modelShoshaFactory->create()->getCollection()->addFieldToFilter('shosha_business_code',$shoshaBusinessCode);
            $aShoshaItem = null;

            foreach ($aShoshaCollections as $aShoshaCollectionItem) {
                $aShoshaItem = $aShoshaCollectionItem;
            }
            if(!$aShoshaItem){
                $errors[] = __('The business code doesn\'t exist');
            }

            $observer->getTransport()->setErrors($errors);

        }


    }
}