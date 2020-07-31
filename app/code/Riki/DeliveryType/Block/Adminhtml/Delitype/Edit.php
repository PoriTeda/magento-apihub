<?php
namespace Riki\DeliveryType\Block\Adminhtml\Delitype;

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
     * Shipleadtime edit block
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'Riki_DeliveryType';
        $this->_controller = 'adminhtml_delitype';

        parent::_construct();

        $this->buttonList->remove('delete');
    }


    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */

    /**
     * Getter of url for "Save and Continue" button
     * tab_id will be replaced by desired by JS later
     *
     * @return string
     */
    protected function _getSaveAndContinueUrl()
    {
        return $this->getUrl('deliverytype/*/save', ['_current' => true, 'back' => 'edit', 'active_tab' => '']);
    }


}