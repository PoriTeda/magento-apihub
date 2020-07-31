<?php

namespace Riki\SerialCode\Block\Adminhtml\SerialCode;

/**
 * Serial code edit form container
 */
class Import extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'Riki_SerialCode';
        $this->_controller = 'adminhtml_serialCode';
        $this->_mode = 'Import';
        parent::_construct();

    }

    /**
     * Get edit form container header text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        return __("Edit serial code '%1'", $this->escapeHtml($this->_coreRegistry->registry('serial_code')->getSerialCode()));
    }
}
