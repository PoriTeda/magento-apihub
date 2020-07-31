<?php

namespace Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab\Products\Column;

class FreeOrDiscount extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Input
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
     * Render product qty field
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $productId = $row->getData('entity_id');
        $courseId = (int)$this->_request->getParam('course_id');
        $machine = $this->_courseFactory->create()->getMachine($courseId, $productId);
        $isFree = $machine && array_key_exists('is_free', $machine) ? $machine['is_free'] : 0;

        $data = [
            ['value' => 1, 'text' => __('Free')],
            ['value' => 0, 'text' => __('Discount')]
        ];

        $html = $this->getOptions($productId, $data, $isFree);

        return $html;
    }

    protected function getOptions($id, $data, $active)
    {
        $html = '<select name="product_machine['.$id.']" class="input-text admin__control-text is-free-select">';

        foreach ($data as $item) {
            $isSelected = $item['value'] == $active ? 'selected' : '';
            $html .= '<option '.$isSelected.' value="'.$item['value'].'" >'.$item['text'].'</option>';
        }

        $html .= '</select>';

        return $html;
    }
}
