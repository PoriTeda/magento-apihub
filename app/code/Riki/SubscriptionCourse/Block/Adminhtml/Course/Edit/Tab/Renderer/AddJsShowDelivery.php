<?php
namespace Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab\Renderer;

class AddJsShowDelivery extends \Magento\Backend\Block\Widget\Form\Renderer\Fieldset\Element implements
    \Magento\Framework\Data\Form\Element\Renderer\RendererInterface
{

    protected $_template = 'Riki_SubscriptionCourse::renderer/form/renderaddjs.phtml';



    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->_element = $element;
        $html = $this->toHtml();
        return $html;
    }
}