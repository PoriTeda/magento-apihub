<?php
namespace Riki\TimeSlots\Block\Adminhtml\Form\Renderer;

class AddJsValidation extends \Magento\Backend\Block\Widget\Form\Renderer\Fieldset\Element implements
    \Magento\Framework\Data\Form\Element\Renderer\RendererInterface
{

    protected $_template = 'Riki_TimeSlots::renderer/form/renderaddjs.phtml';



    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->_element = $element;
        $html = $this->toHtml();
        return $html;
    }
}