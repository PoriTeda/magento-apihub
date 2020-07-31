<?php
namespace Riki\Subscription\Controller\Adminhtml\Multiple\Category\Campaign;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Psr\Log\LoggerInterface;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Riki\Subscription\Model\Multiple\Category\CampaignFactory;
use Magento\Framework\App\RequestInterface;

/**
 * Class AbstractCampaign
 * @package Riki\Subscription\Controller\Adminhtml\Multiple\Category\Campaign
 */
abstract class AbstractCampaign extends Action
{
    /**
     * ACL name
     */
    const ADMIN_RESOURCE = 'Riki_Subscription::multiple_category_campaign';

    const ADMIN_RESOURCE_VIEW = 'Riki_Subscription::multiple_category_campaign_listview';

    const ADMIN_RESOURCE_EDIT = 'Riki_Subscription::multiple_category_campaign_edit';

    const ADMIN_RESOURCE_DELETE = 'Riki_Subscription::multiple_category_campaign_delete';

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
     * @var CampaignFactory
     */
    protected $campaignFactory;

    /**
     * AbstractCampaign constructor.
     * @param Action\Context $context
     * @param Registry $registry
     * @param ForwardFactory $resultForwardFactory
     * @param CampaignFactory $campaignFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        Action\Context $context,
        Registry $registry,
        ForwardFactory $resultForwardFactory,
        CampaignFactory $campaignFactory,
        LoggerInterface $logger
    ) {
        $this->registry = $registry;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->campaignFactory = $campaignFactory;
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
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Multiple Categories'));
        return $resultPage;
    }

    /**
     * @param RequestInterface $request
     * @return \Magento\Framework\App\ResponseInterface
     * @throws NotFoundException
     */
    public function dispatch(RequestInterface $request)
    {
        return parent::dispatch($request);
    }
}
