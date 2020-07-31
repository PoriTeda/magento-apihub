<?php
namespace Riki\ShipLeadTime\Controller\Adminhtml\Index;

use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Class AbstractMassStatus
 */
abstract class AbstractMassAction extends \Magento\Backend\App\Action
{
    /**
     * @var string
     */
    protected $redirectUrl = '*/*/index';

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    protected $leadTimeRepository;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param \Riki\ShipLeadTime\Api\LeadtimeRepositoryInterface $leadtimeRepositoryInterface
     * @param \Riki\ShipLeadTime\Model\ResourceModel\Leadtime\CollectionFactory $collectionFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        \Riki\ShipLeadTime\Api\LeadtimeRepositoryInterface $leadtimeRepositoryInterface,
        \Riki\ShipLeadTime\Model\ResourceModel\Leadtime\CollectionFactory $collectionFactory
    )
    {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->leadTimeRepository = $leadtimeRepositoryInterface;
    }

    /**
     * Execute action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @throws \Magento\Framework\Exception\LocalizedException|\Exception
     */
    public function execute()
    {
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            return $this->massAction($collection);
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath($this->redirectUrl);
        }
    }

    /**
     * Check the permission to Manage Lead times
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_ShipLeadTime::shipleadtime_edit');
    }

    /**
     * Return component referer url
     * TODO: Technical dept referer url should be implement as a part of Action configuration in in appropriate way
     *
     * @return null|string
     */
    protected function getComponentRefererUrl()
    {
        return $this->filter->getComponentRefererUrl()?: $this->_redirect->getRefererUrl();
    }

    /**
     *
     */
    abstract protected function massAction(\Riki\ShipLeadTime\Model\ResourceModel\Leadtime\Collection $collection);
}
