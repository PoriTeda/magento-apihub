<?php
namespace Riki\Customer\Observer;
use Magento\Framework\Exception\InvalidEmailOrPasswordException;

class LoginCustomerMultipleWebsite implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * LoginCustomerMultipleWebsite constructor.
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager
    )
    {
        $this->storeManager = $storeManager;
    }

    /**
     * Execute
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @throws InvalidEmailOrPasswordException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $customerModel = $observer->getModel();

        if(null != $customerModel){
            $multipleWebsites = $customerModel->getMultipleWebsite();

            if(null !== $multipleWebsites && '' !== $multipleWebsites){
                $multipleWebsitesArray = explode(",",$customerModel->getMultipleWebsite());
                if(!in_array($this->storeManager->getStore()->getWebsiteId(),$multipleWebsitesArray)){
                    throw new InvalidEmailOrPasswordException(__('Invalid login or password.'));
                }
            }
            else{
                $multipleWebsitesArray = array($customerModel->getWebsiteId());
                if(!in_array($this->storeManager->getStore()->getWebsiteId(),$multipleWebsitesArray)){
                    throw new InvalidEmailOrPasswordException(__('Invalid login or password.'));
                }
            }
        }

    }
}