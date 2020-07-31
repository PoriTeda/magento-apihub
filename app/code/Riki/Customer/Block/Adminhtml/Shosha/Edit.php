<?php
namespace Riki\Customer\Block\Adminhtml\Shosha;

use Magento\Backend\Block\Widget\Form\Container;

class Edit extends Container
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
     * Shosha Header edit block
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'Riki_Customer';
        $this->_controller = 'Adminhtml_Shosha';

        parent::_construct();

        if ($this->_isAllowedAction('Riki_Customer::shoshacustomer_save')) {
            $this->buttonList->update('save', 'label', __('Save Shosha Business Code'));
            $this->buttonList->add(
                'saveandcontinue',
                [
                    'label' => __('Save and Continue Edit'),
                    'class' => 'save',
                    'data_attribute' => [
                        'mage-init' => [
                            'button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form'],
                        ],
                    ]
                ],
                -100
            );
        } else {
            $this->buttonList->remove('save');
        }
    }

    /**
     * Get header with category name
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        if (null != $this->_coreRegistry->registry('shoshacustomer') && $this->_coreRegistry->registry('shoshacustomer')->getShoshaBusinessCode()) {
            return __("Edit Shosha Business Code '%1'", $this->escapeHtml($this->_coreRegistry->registry('shoshacustomer')->getShoshaBusinessCode()));
        } else {
            return __('Add New Shosha Business');
        }
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    /**
     * Getter of url for "Save and Continue" button
     * tab_id will be replaced by desired by JS later
     *
     * @return string
     */
    protected function _getSaveAndContinueUrl()
    {
        return $this->getUrl('shosha/*/save', ['_current' => true, 'back' => 'edit', 'active_tab' => '']);
    }
}