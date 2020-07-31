<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Riki\Checkout\Plugin\Cart;

use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

class RedirectIndex
{
    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    protected $resultRedirectFactory;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;
    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    protected $redirect;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $session;
    /**
     * @var \Magento\UrlRewrite\Controller\Router
     */
    protected $router;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var UrlFinderInterface
     */
    protected $urlFinder;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $_courseFactory;

    /**
     * RedirectIndex constructor.
     * @param \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\App\Response\RedirectInterface $redirect
     * @param \Magento\Checkout\Model\Session $session
     * @param \Magento\UrlRewrite\Controller\Router $router
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param UrlFinderInterface $urlFinder
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
     */
    public function __construct(
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\App\Response\RedirectInterface $redirect,
        \Magento\Checkout\Model\Session $session,
        \Magento\UrlRewrite\Controller\Router $router,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        UrlFinderInterface $urlFinder,
        \Magento\Framework\App\RequestInterface $request,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->urlBuilder = $urlBuilder;
        $this->redirect = $redirect;
        $this->session = $session;
        $this->router = $router;
        $this->storeManager = $storeManager;
        $this->urlFinder = $urlFinder;
        $this->_request = $request;
        $this->_courseFactory = $courseFactory;
    }

    public function aroundExecute(\Magento\Checkout\Controller\Cart\Index $subject, callable $proceed)
    {
        $result = $this->resultRedirectFactory->create();
        $url = $this->redirect->getRefererUrl();
        $this->session->setCartRefererUrl($url);
        $resultPage = $result->setPath($this->urlBuilder->getUrl('checkout').'#single_order_confirm');
        return $resultPage;
    }

    public function getRewrite($requestPath, $storeId)
    {
        return $this->urlFinder->findOneByData(
            [
                UrlRewrite::REQUEST_PATH => ltrim($requestPath, '/'),
                UrlRewrite::STORE_ID => $storeId,
            ]
        );
    }

    /**
     * Check subscription is hanpukai subscription or not
     *
     */

    public function isHanpukaiSubscription($courseCode)
    {
        $courseModel = $this->_courseFactory->create()->getCollection()
            ->addFieldToFilter('course_code', $courseCode)
            ->addFieldToSelect(['course_id','subscription_type'])->getData();
        if (count($courseModel) > 0) {
            if ($courseModel[0]['subscription_type'] == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI) {
                return true;
            }
        }
        return false;
    }

    /**
     * checkQuoteIsCourseNormal
     */
    public function checkQuoteIsCourseNormal() {
        $quote = $this->session->getQuote();

        if (!empty($quote->getId())) {
            $rikiHanpukaiQty = $quote->getData('riki_hanpukai_qty');
            $rikiCourseId = $quote->getData('riki_course_id');
            if (!empty($rikiCourseId) && empty($rikiHanpukaiQty)) {
                return true;
            }
        }

        return false;
    }
}
