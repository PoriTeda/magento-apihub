<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\SubscriptionCourse\Controller\Adminhtml\Course;

use Magento\Framework\Controller\ResultFactory;

class MassEnable extends \Magento\Backend\App\Action
{
    protected $_filter;

    protected $_collectionFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Ui\Component\MassAction\Filter $filter,
        \Riki\SubscriptionCourse\Model\ResourceModel\Course\CollectionFactory $collectionFactory,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_filter = $filter;
        $this->_collectionFactory = $collectionFactory;
        $this->_logger = $logger;
        parent::__construct($context);
    }

    /**
     * Delete courses
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $collection = $this->_filter->getCollection($this->_collectionFactory->create());
        $collectionSize = $collection->getSize();

        try {
            foreach ($collection as $item) {
                $item->setIsEnable(true);
                $item->save();
            }
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        $this->messageManager->addSuccess(__('A total of %1 record(s) have been enabled.', $collectionSize));

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_SubscriptionCourse::save');
    }
}
