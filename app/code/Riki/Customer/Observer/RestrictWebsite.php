<?php

namespace Riki\Customer\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class RestrictWebsite implements ObserverInterface
{
    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var \Magento\Framework\DataObject\Factory
     */
    protected $objectFactory;

    /**
     * @var \Magento\Framework\App\Response\Http
     */
    protected $response;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $url;

    /**
     * @var \Magento\Framework\App\ActionFlag
     */
    protected $actionFlag;

    /**
     * @var \Riki\Customer\Model\WebsiteRestrictionValidator
     */
    protected $websiteRestrictionValidator;

    /**
     * @var \Magento\Framework\Url\Helper\Data
     */
    protected $urlHelper;

    /**
     * @var array
     */
    protected $allowedActions = [];

    /**
     * RestrictWebsite constructor.
     *
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Framework\DataObject\Factory $objectFactory
     * @param \Magento\Framework\App\Response\Http $response
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\UrlInterface $url
     * @param \Magento\Framework\App\ActionFlag $actionFlag
     * @param \Riki\Customer\Model\WebsiteRestrictionValidator $websiteRestrictionValidator
     * @param \Magento\Framework\Url\Helper\Data $urlHelper,
     * @param array $allowedActions
     */
    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\DataObject\Factory $objectFactory,
        \Magento\Framework\App\Response\Http $response,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\App\ActionFlag $actionFlag,
        \Riki\Customer\Model\WebsiteRestrictionValidator $websiteRestrictionValidator,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        $allowedActions = []
    )
    {
        $this->eventManager = $eventManager;
        $this->objectFactory = $objectFactory;
        $this->response = $response;
        $this->customerSession = $customerSession;
        $this->url = $url;
        $this->actionFlag = $actionFlag;
        $this->websiteRestrictionValidator = $websiteRestrictionValidator;
        $this->urlHelper = $urlHelper;
        $this->allowedActions = $allowedActions;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /* @var $controller \Magento\Framework\App\Action\Action */
        $controller = $observer->getEvent()->getControllerAction();

        $dispatchResult = $this->objectFactory->create(['should_proceed' => true, 'customer_logged_in' => false]);
        $this->eventManager->dispatch(
            'riki_customer_website_restriction_frontend',
            ['controller' => $controller, 'result' => $dispatchResult]
        );

        if (!$dispatchResult->getShouldProceed()) {
            return;
        }

        $fullActionName = $observer->getEvent()->getRequest()->getFullActionName();
        if (in_array($fullActionName, $this->allowedActions)) {
            return;
        }

        if (!$this->websiteRestrictionValidator->validate()) {
            if (!$controller->getRequest()->isAjax()) {
                $redirectUrl = $this->url->getUrl('customer/account', ['_scope' => 'ec']);
                if (!$this->customerSession->isLoggedIn()) {
                    $redirectUrl = $this->url->getUrl('customer/account/login', [
                        \Magento\Framework\App\ActionInterface::PARAM_NAME_URL_ENCODED => $this->urlHelper->getCurrentBase64Url()
                    ]);
                }

                $this->response->setRedirect($redirectUrl);
            } else {
                $this->response->setHttpResponseCode(400);
            }

            $this->actionFlag->set('', \Magento\Framework\App\ActionInterface::FLAG_NO_DISPATCH, true);
        }
    }
}
