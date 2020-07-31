<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\SubscriptionCourse\Controller\Adminhtml\Course;

use Magento\Framework\Controller\ResultFactory;

class MassDelete extends \Magento\Backend\App\Action
{
    protected $_filter;

    protected $_collectionFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $profileHelper;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Ui\Component\MassAction\Filter $filter,
        \Riki\SubscriptionCourse\Model\ResourceModel\Course\CollectionFactory $collectionFactory,
        \Psr\Log\LoggerInterface $logger,
        \Riki\Subscription\Helper\Profile\Data $profileHelper
    ) {
        $this->_filter = $filter;
        $this->_collectionFactory = $collectionFactory;
        $this->_logger = $logger;
        $this->profileHelper = $profileHelper;
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
        $size = 0;
        try {
            foreach ($collection as $item) {
                $checkCourseIsExistedInProfile = $this->profileHelper->checkCourseIsExistedInProfile($item->getId());
                if(!$checkCourseIsExistedInProfile){
                    $this->messageManager->addError(__('We cannot delete course').' #'.$item->getId().__(' because it exist in subscription profile'));
                }else {
                    $item->delete();
                    $size ++;
                }
            }
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        if($size > 0) {
            $this->messageManager->addSuccess(__('A total of %1 record(s) have been deleted.', $size));
        }
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_SubscriptionCourse::delete');
    }
}
