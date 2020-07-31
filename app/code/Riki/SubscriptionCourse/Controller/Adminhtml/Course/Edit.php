<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\SubscriptionCourse\Controller\Adminhtml\Course;

class Edit extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    protected $_typeHelper;

    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $_courseFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Riki\SubscriptionCourse\Helper\Type $type
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Riki\SubscriptionCourse\Helper\Type $type,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
    ) {
        $this->_courseFactory = $courseFactory;
        $this->_resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $coreRegistry;
        $this->_typeHelper = $type;
        parent::__construct($context);
    }

    /**
     * Edit Course
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $id = $this->getRequest()->getParam('course_id');
        $model = $this->_courseFactory->create();

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This course no longer exists.'));
                return $resultRedirect->setPath('*/*/');
            }

            $type = $model->getSubscriptionType();
            if($type == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI){
                $type = $model->getHanpukaiType();
            }
        }else{
            $type = $this->getRequest()->getParam('type', \Riki\SubscriptionCourse\Model\Course\Type::DEFAULT_TYPE);
            $model = $this->_typeHelper->prepareTypeForNewObject($model, $type);
        }

        $data = $this->_session->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        $this->_coreRegistry->register('subscription_course', $model);

        $resultPage = $this->_resultPageFactory->create();

        $resultPage->addHandle(['subscription_course_' . $this->_typeHelper->getLayoutNameByType($type)]);

        $resultPage->setActiveMenu('Riki_SubscriptionCourse::course')
            ->addBreadcrumb(__('Course'), __('Course'));
        $resultPage->addBreadcrumb(
            $id ? __('Edit Course') : __('New Course'),
            $id ? __('Edit Course') : __('New Course')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Subscription Course'));
        $resultPage->getConfig()->getTitle()->prepend($this->_typeHelper->getTitlePageByType($model));
        return $resultPage;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_SubscriptionCourse::save');
    }
}
