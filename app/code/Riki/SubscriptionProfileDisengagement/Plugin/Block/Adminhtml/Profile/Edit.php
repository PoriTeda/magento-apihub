<?php
namespace Riki\SubscriptionProfileDisengagement\Plugin\Block\Adminhtml\Profile;

class Edit
{
    protected $_productCollection;

    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
    ){
        $this->_productCollection = $productCollectionFactory;
    }

    /**
     * get without delivery product list for disengaged profile
     *
     * @param \Riki\Subscription\Block\Adminhtml\Profile\Edit $subject
     * @param \Closure $proceed
     * @return mixed
     */
    public function aroundGetListProductOfCourse(
        \Riki\Subscription\Block\Adminhtml\Profile\Edit $subject,
        \Closure $proceed
    ) {

        $profile = $subject->getEntity();

        if(
            $profile->getDisengagementDate()
            && $profile->getDisengagementReason()
            && $profile->getDisengagementUser()
            && $profile->getStatus()
        ){
            return $this->getWithoutDeliveryProduct();
        }

        return $proceed();
    }

    public function getWithoutDeliveryProduct(){
        return $this->_productCollection->create();
    }
}
