<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\SubscriptionCourse\Plugin\Controller\Cart;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Controller\ResultFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Add
{

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    protected $resultFactory;

    /* @var \Riki\SubscriptionCourse\Helper\Data */
    protected $subCourseHelperData;

    /* @var \Riki\SubscriptionCourse\Model\Course */
    protected $subCourseModel;

    /* @var \Magento\Framework\Json\Helper\Data */
    protected $jsonHelperData;

    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    protected $redirect;

    public function __construct(
        \Magento\Framework\Json\Helper\Data $jsonHelperData,
        \Riki\SubscriptionCourse\Model\Course $subCourseModel,
        \Riki\SubscriptionCourse\Helper\Data $subCourseHelperData,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        \Magento\Framework\App\Response\RedirectInterface $redirect,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Checkout\Model\Cart $cart
    )
    {
        $this->jsonHelperData = $jsonHelperData;
        $this->subCourseModel = $subCourseModel;
        $this->subCourseHelperData = $subCourseHelperData;
        $this->messageManager = $messageManager;
        $this->resultFactory = $resultFactory;
        $this->redirect = $redirect;
        $this->categoryFactory = $categoryFactory;
        $this->cart = $cart;
    }

    public function aroundDispatch(
        \Magento\Checkout\Controller\Cart\Add $subject,
        \Closure $process,
        \Magento\Framework\App\RequestInterface $request
    ) {

        $quote = $this->cart->getQuote();

        $courseId = $quote->getData("riki_course_id");

        if(empty($courseId)) {
            return $process($request); // Do not have riki_course_id check nothing
        }

//        $productId = (int)$request->getParam('product');
//
//        $arrProductId = [$productId];
//        foreach($quote->getAllVisibleItems() as $item) {
//            $buyRequest = $item->getBuyRequest();
//            if (isset($buyRequest['options']['ampromo_rule_id'])) {
//                continue;
//            }
//            if ($item->getData('is_riki_machine') == 1) {
//                continue;
//            }
//            $arrProductId[] = $item->getProductId();
//        }
//        $qty = $quote->getItemsQty();
//
//        $errorCode = $this->isNotValid($courseId, $arrProductId, $qty);
//
//
//        if ($this->subCourseHelperData->getSubscriptionCourseType($courseId) == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI) {
//            $errorCode = 2;
//        }

        /**
         * Update Rule If Quote Exist Subscription => Not Allow Add Product From PDP
         */
        if ($courseId) {
            $errorCode = 1;
        }
        switch ($errorCode) {
            case 1:
                $this->messageManager->addError(__("shopping cart cannot contain SPOT and subscription at the same time"));
                break;
            case 2:
                $this->messageManager->addError(__("can't not add product when shopping cart had hanpukai subscription"));
                break;
            default:
                // Do nothing
        }

        if( $errorCode === 0 ){
            return $process($request);
        }

        // \Magento\Framework\Controller\ResultFactory $resultFactory,
        // \Magento\Framework\App\Response\RedirectInterface $redirect
        // use Magento\Framework\Controller\ResultFactory;
        $backUrl = $this->redirect->getRefererUrl();
        if ( !$request->isAjax() ) {
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->redirect->getRefererUrl());
            return $resultRedirect;
        }

        $result = [];

        if ($backUrl) {
            $result['backUrl'] = $backUrl;
        }

        return $subject->getResponse()->representJson(
            $this->jsonHelperData->jsonEncode($result)
        );

    }

    protected function isNotValid($courseId, $arrProductId, $qty)
    {
        $objCourse = $this->subCourseModel;
        $objCourse->load($courseId);

        if(empty($objCourse->getId())) { // not yet set course_id in quote
            return 0;
        }

        // Check product must belong course
        $errorCode = $this->subCourseHelperData->checkCartIsValidForCourse($arrProductId, $courseId);

        if($errorCode === 1) return 1; // have spot


        return 0;
    }
}
