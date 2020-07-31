<?php
/**
 * PHP version 7
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @category Riki_Shipment
 * @package  Riki\Shipment\Controller\Adminhtml\Order\Shipment
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://shop.nestle.jp
 */
namespace Riki\Shipment\Controller\Adminhtml\Order\Shipment;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class Email
 *
 * @category Riki_Shipment
 * @package  Riki\Shipment\Controller\Adminhtml\Order\Shipment
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://shop.nestle.jp
 */
class Email extends \Magento\Shipping\Controller\Adminhtml\Order\Shipment\Email
{
    /**
     * @var \Riki\ShipmentImporter\Helper\Email
     */
    protected $emailHelper;
    /**
     * @var \Riki\ShipmentImporter\Helper\Data
     */
    protected $dataHelper;
    /**
     * Email    constructor.
     * @param   Action\Context $context
     * @param   \Riki\ShipmentImporter\Helper\Email $emailHelper
     */
    public function __construct(
        Action\Context $context,
        \Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader $shipmentLoader,
        \Riki\ShipmentImporter\Helper\Email $emailHelper,
        \Riki\ShipmentImporter\Helper\Data $dataHelper

    )
    {
        $this->emailHelper = $emailHelper;
        $this->dataHelper = $dataHelper;
        parent::__construct($context,$shipmentLoader);
    }

    /**
     * Send email with shipment data to customer
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $this->shipmentLoader->setOrderId($this->getRequest()->getParam('order_id'));
        $this->shipmentLoader->setShipmentId($this->getRequest()->getParam('shipment_id'));
        $this->shipmentLoader->setShipment($this->getRequest()->getParam('shipment'));
        $this->shipmentLoader->setTracking($this->getRequest()->getParam('tracking'));
        $shipment = $this->shipmentLoader->load();

        if($shipment->getAllTracks())
        {
            $emailTemplateVariables= $this->emailHelper->getEmailParameters($shipment);
            $this->dataHelper->sendTrackingCodeEmail(
                $emailTemplateVariables
            );
            $this->messageManager->addSuccess(__('You sent the shipment.'));

        }else {
            $this->messageManager->addError(__('Cannot send shipment information. Tracking Number not found'));
        }
        $resultRedirect = $this->resultFactory->create(
            ResultFactory::TYPE_REDIRECT
        );
        return $resultRedirect->setPath(
            '*/*/view', ['shipment_id' =>
                $this->getRequest()->getParam('shipment_id')]
        );
    }
}