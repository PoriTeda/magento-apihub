<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Riki\SubscriptionPage\Block\Html;

class Breadcrumbs extends \Magento\Theme\Block\Html\Breadcrumbs
{
    /**
     * @var \Riki\SubscriptionCourse\Model\ResourceModel\Course\CollectionFactory
     */
    protected $courseCollectionFactory;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Riki\SubscriptionCourse\Model\ResourceModel\Course\CollectionFactory $courseCollectionFactory,
        array $data = []
    ) {
        $this->courseCollectionFactory = $courseCollectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * get product by course code
     */
    public function getSubscriptionPage(){
        $subscription = $this->setSubscriptionBYCourseCode();
        if($subscription!=null){
            $this->addCrumb(
                'navigation_path',
                [
                    'dataHtml'=>$subscription->getData('navigation_path'),
                ]
            );

            $this->addCrumb(
                'subscription_page',
                [
                    'label' => __($subscription->getName()),
                    'title' => __($subscription->getName()),
                ]
            );
        }
    }

    protected function _prepareLayout()
    {
        $this->addCrumb(
            'home',
            [
                'label' => __('HomePage'),
                'title' => __('Go to Home Page'),
                'link' => $this->_storeManager->getStore()->getBaseUrl()
            ]
        );

        //add breadcrumbs product
        $this->getSubscriptionPage();

        return parent::_prepareLayout();
    }

    /**
     * @return \Magento\Framework\DataObject|null
     */
    public function setSubscriptionBYCourseCode()
    {
        $courseObj = null;
        $courseCode =$this->getRequest()->getParam('code');
        if ($courseCode !=null)
        {
            $courseCollection = $this->courseCollectionFactory->create()
                                     ->addFieldToFilter('course_code',$courseCode)->setPageSize(1);
            if ($courseCollection->getSize() > 0) {
                $courseObj = $courseCollection->getFirstItem();
            }
        }
        return $courseObj;
    }

}