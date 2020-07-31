<?php
namespace Riki\Rma\Controller\Adminhtml\Rma;

use Magento\Backend\App\Action;
use Magento\Rma\Model\Rma as RmaModel;

class Edit extends \Magento\Rma\Controller\Adminhtml\Rma\Edit
{
    /**
     * @var \Riki\Rma\Helper\Data
     */
    private $rmaHelper;

    /**
     * Edit constructor.
     * @param Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Shipping\Helper\Carrier $carrierHelper
     * @param \Magento\Rma\Model\Shipping\LabelService $labelService
     * @param RmaModel\RmaDataMapper $rmaDataMapper
     * @param \Riki\Rma\Helper\Data $rmaHelper
     */
    public function __construct(
        Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Shipping\Helper\Carrier $carrierHelper,
        \Magento\Rma\Model\Shipping\LabelService $labelService,
        RmaModel\RmaDataMapper $rmaDataMapper,
        \Riki\Rma\Helper\Data $rmaHelper
    ) {
    
        $this->rmaHelper = $rmaHelper;

        parent::__construct(
            $context,
            $coreRegistry,
            $fileFactory,
            $filesystem,
            $carrierHelper,
            $labelService,
            $rmaDataMapper
        );
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        try {
            $model = $this->_initModel();
            $this->_coreRegistry->unregister('current_rma');
            $this->_coreRegistry->unregister('current_order');
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $model = null;
        }

        if ($model) {
            if ($changedFields = $model->getExtensionData('need_save_again')) {
                if (isset($changedFields['trigger_cancel_point'])) {
                    $message = __('Some changes in RMA #%1 affect this RMA.', $changedFields['trigger_cancel_point']);
                } else {
                    $message = __('Data has been changed for: %1.', implode(', ', $changedFields));
                }

                $this->messageManager->addWarning($message);
                $this->messageManager->addWarning(
                    __('This RMA need to be reviewed and saved again before doing further actions.')
                );
            }

            //
            $order = $model->getOrder();

            if ((float)$this->rmaHelper->getReturnsAmountTotalByOrder($order) > (float)$order->getGrandTotal()) {
                $this->messageManager->addWarning(
                    __('Returned amount is greater than order. Please double check')
                );
            }
        }

        parent::execute();
    }

    /**
     * Check the permission
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Rma::rma_return_actions_view_comment');
    }
}
