<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\ShippingCarrier\Block\Adminhtml;

use Riki\Sales\Helper\CheckRoleViewOnly;

class View extends \Magento\Shipping\Block\Adminhtml\View
{

    /**
     * @var CheckRoleViewOnly
     */
    protected $checkRoleOnly;


    /**
     * View constructor.
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param CheckRoleViewOnly $checkRoleOnly
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        CheckRoleViewOnly $checkRoleOnly,
        array $data = []
    )
    {
        $this->checkRoleOnly = $checkRoleOnly;
        parent::__construct($context, $registry, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'shipment_id';
        $this->_mode = 'view';

        parent::_construct();

        $this->buttonList->remove('reset');
        $this->buttonList->remove('delete');
        if (!$this->getShipment()) {
            return;
        }

        if ($this->_authorization->isAllowed('Magento_Sales::emails')) {
            $this->buttonList->update('save', 'label', __('Send Tracking Information'));
            $this->buttonList->update(
                'save',
                'onclick',
                "deleteConfirm('" . __(
                    'Are you sure you want to send a Shipment email to customer?'
                ) . "', '" . $this->getEmailUrl() . "')"
            );
        }

        if ($this->checkRoleOnly->checkViewShipmentOnly(CheckRoleViewOnly::SHIPMENT_VIEW_ONLY)) {
            $this->buttonList->remove('save');
        }

        if ($this->getShipment()->getId()) {
            $this->buttonList->add(
                'print',
                [
                    'label' => __('Print'),
                    'class' => 'save',
                    'onclick' => 'setLocation(\'' . $this->getPrintUrl() . '\')'
                ]
            );
        }
    }

}
