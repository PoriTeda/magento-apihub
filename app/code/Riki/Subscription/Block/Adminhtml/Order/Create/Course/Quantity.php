<?php
namespace Riki\Subscription\Block\Adminhtml\Order\Create\Course;

use Magento\Framework\View\Element\Template;
use Riki\SubscriptionCourse\Model\Course as CourseModel;
use Riki\SubscriptionCourse\Model\Course\Type;

class Quantity extends Template
{
    /**
     * @var CourseModel
     */
    protected $_courseModel;

    public function __construct(
        Template\Context $context,
        CourseModel $courseModel,
        array $data = []
    ) {
        $this->_courseModel = $courseModel;
        parent::__construct($context, $data);
    }

    public function isHanpukai(){
        $courseId = $this->getRequest()->getParam('id');
        $course = $this->_courseModel->load($courseId);
        if ($course->getId()) {
            return $course->getData('subscription_type') == Type::TYPE_HANPUKAI;
        }
        return false;
    }
}