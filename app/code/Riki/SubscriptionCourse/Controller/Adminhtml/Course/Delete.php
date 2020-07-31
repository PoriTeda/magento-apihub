<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\SubscriptionCourse\Controller\Adminhtml\Course;

class Delete extends \Magento\Backend\App\Action
{
    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $_courseFactory;
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $profileHelper;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Riki\Subscription\Helper\Profile\Data $profileHelper
    ) {
        $this->_courseFactory = $courseFactory;
        $this->profileHelper = $profileHelper;
        parent::__construct($context);
    }

    /**
     * Delete Course
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('course_id');
        $resultRedirect = $this->resultRedirectFactory->create();
        $checkCourseIsExistedInProfile = $this->profileHelper->checkCourseIsExistedInProfile($id);
        if(!$checkCourseIsExistedInProfile){
            $this->messageManager->addError(__('We cannot delete the course exist in subscription profile'));
            return $resultRedirect->setPath('*/*/edit', ['course_id' => $id]);
        }

        if ($id) {
            try {
                $model = $this->_courseFactory->create();
                $model->load($id);
                $model->delete();
                $this->messageManager->addSuccess(__('The course has been deleted.'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['course_id' => $id]);
            }
        }

        $this->messageManager->addError(__('This course no longer exists.'));
        return $resultRedirect->setPath('*/*/');
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_SubscriptionCourse::delete');
    }
}
