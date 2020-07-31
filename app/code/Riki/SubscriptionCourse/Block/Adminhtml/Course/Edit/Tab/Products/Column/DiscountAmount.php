<?php

namespace Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab\Products\Column;

class DiscountAmount extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Input
{
    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $_courseFactory;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @param \Magento\Backend\Block\Context $context
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Magento\Framework\Registry $coreRegistry,
        array $data = []
    ) {
        $this->_courseFactory = $courseFactory;
        $this->_request = $context->getRequest();
    }

    /**
     * Render product field
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $productId = $row->getData('entity_id');
        $courseId = (int)$this->_request->getParam('course_id');
        $machine = $this->_courseFactory->create()->getMachine($courseId, $productId);
        $discountAmount = $machine && array_key_exists('discount_amount', $machine) ? $machine['discount_amount'] : 0;

        $html = '<input type="text" name="discount_amount" value="'.$discountAmount.'" class="input-text ">';

        return $html;
    }
}
