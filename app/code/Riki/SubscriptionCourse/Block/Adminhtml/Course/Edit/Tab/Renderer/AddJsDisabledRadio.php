<?php

namespace Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab\Renderer;

class AddJsDisabledRadio extends \Magento\Backend\Block\Widget\Form\Renderer\Fieldset\Element implements
    \Magento\Framework\Data\Form\Element\Renderer\RendererInterface
{

    protected $_template = 'Riki_SubscriptionCourse::renderer/form/renderjsdisabledradio.phtml';

    /* @var \Magento\Framework\Registry */
    protected $registry;
    /**
     * Construct
     *
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ){
        $this->registry = $registry;
        parent::__construct($context, $data);
    }

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->_element = $element;
        $html = $this->toHtml();
        return $html;
    }

    public function isDisabledRadio()
    {
        return $this->registry->registry('hanpukai_disable_radion_in_setting_tab');
    }
}