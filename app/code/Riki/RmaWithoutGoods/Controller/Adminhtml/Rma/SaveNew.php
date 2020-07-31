<?php

namespace Riki\RmaWithoutGoods\Controller\Adminhtml\Rma;

use Magento\Backend\App\Action;

class SaveNew extends \Magento\Rma\Controller\Adminhtml\Rma\SaveNew
{
    protected $_logger;

    protected $_generic;

    public function __construct(
        Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Shipping\Helper\Carrier $carrierHelper,
        \Magento\Rma\Model\Shipping\LabelService $labelService,
        \Psr\Log\LoggerInterface $loggerInterface,
        \Magento\Framework\Session\Generic $generic,
        \Magento\Rma\Model\Rma\RmaDataMapper $rmaDataMapper
    ){

        $this->_logger = $loggerInterface;
        $this->_generic = $generic;

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
     * Save new RMA request
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        if (!$this->getRequest()->isPost() || $this->getRequest()->getParam('back', false)) {
            $this->_redirect('adminhtml/*/');
            return;
        }
        try {
            /** @var $model \Magento\Rma\Model\Rma */
            $model = $this->_initModel();
            $saveRequest = $this->getRequest()->getPostValue();
            $saveRequest['is_without_goods'] = 1;
            $model->setData(
                $this->rmaDataMapper->prepareNewRmaInstanceData(
                    $saveRequest,
                    $this->_coreRegistry->registry('current_order')
                )
            );

            $model->setIsWithoutGoods(1);

            if (!$model->saveRma($saveRequest)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('We can\'t save this RMA.'));
            }
            $this->_processNewRmaAdditionalInfo($saveRequest, $model);
            $this->messageManager->addSuccess(__('You submitted the RMA request.'));
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
            $errorKeys = $this->_generic->getRmaErrorKeys();
            $controllerParams = ['order_id' => $this->_coreRegistry->registry('current_order')->getId()];
            if (!empty($errorKeys) && isset($errorKeys['tabs']) && $errorKeys['tabs'] == 'items_section') {
                $controllerParams['active_tab'] = 'items_section';
            }
            $this->_redirect('rma_wg/rma/newAction', $controllerParams);
            return;
        } catch (\Exception $e) {
            $this->messageManager->addError(__('We can\'t save this RMA.'));
            $this->_logger->critical($e);
        }
        $this->_redirect('adminhtml/*/');
    }

    /**
     * Check the permission
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_RmaWithoutGoods::rma_wg_actions_save');
    }
}
