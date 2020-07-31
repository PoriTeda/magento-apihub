<?php

namespace Riki\Subscription\Controller\Adminhtml\Landing\Page;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Psr\Log\LoggerInterface;
use Riki\Subscription\Model\Landing\PageFactory;

/**
 * Class AbstractCampaign
 *
 * @package Riki\Subscription\Controller\Adminhtml\Landing\Page
 */
abstract class AbstractPage extends Action
{
    /**
     * ACL name
     */
    const ADMIN_RESOURCE = 'Riki_Subscription::landing_page_management';

    const ADMIN_RESOURCE_VIEW = 'Riki_Subscription::landing_page_listview';

    const ADMIN_RESOURCE_EDIT = 'Riki_Subscription::landing_page_edit';

    const ADMIN_RESOURCE_DELETE = 'Riki_Subscription::landing_page_delete';

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var \Magento\Backend\Model\View\Result\ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var PageFactory
     */
    protected $landingPageFactory;

    /**
     * AbstractCampaign constructor.
     *
     * @param Action\Context $context
     * @param Registry $registry
     * @param ForwardFactory $resultForwardFactory
     * @param PageFactory $landingPageFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        Action\Context $context,
        Registry $registry,
        ForwardFactory $resultForwardFactory,
        PageFactory $landingPageFactory,
        LoggerInterface $logger
    )
    {
        $this->registry = $registry;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->landingPageFactory = $landingPageFactory;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function initResultPage()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu(self::ADMIN_RESOURCE);
        $resultPage->getConfig()->getTitle()->prepend(__('Landing Page Management'));
        return $resultPage;
    }
}
