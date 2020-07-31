<?php

namespace Riki\SubscriptionCourse\Controller\Adminhtml\Import;

use Magento\Framework\Controller\ResultFactory;

class Index extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    protected $resultFactory;

    public function __construct(\Magento\Backend\App\Action\Context $context)
    {
        parent::__construct($context);
        $this->resultFactory = $context->getResultFactory();
    }

    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('subscription/course/index');
        return $resultRedirect;
    }
}
