<?php

namespace Riki\GiftWrapping\Controller\Adminhtml\Giftwrapping;

use Magento\Framework\Controller\ResultFactory;

class Save extends \Magento\GiftWrapping\Controller\Adminhtml\Giftwrapping\Save
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $log;
    /**
     * @var  \Riki\GiftWrapping\Helper\GiftWrapping
     */
    protected $_giftWrappingHelper;

    /**
     * Save constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Psr\Log\LoggerInterface $loggerInterface
     * @param \Riki\GiftWrapping\Helper\GiftWrapping $giftWrapping
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Psr\Log\LoggerInterface $loggerInterface,
        \Riki\GiftWrapping\Helper\GiftWrapping $giftWrapping
    ) {
        $this->log = $loggerInterface;
        $this->_giftWrappingHelper = $giftWrapping;
        parent::__construct($context, $coreRegistry);
    }

    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $wrappingRawData = $this->_prepareGiftWrappingRawData($this->getRequest()->getPost('wrapping'));
        if ($wrappingRawData) {
            try {
                $model = $this->_initModel();
                // Check gift code
                $giftCodeExist = $this->_giftWrappingHelper->checkGiftCode($wrappingRawData['gift_code']);
                if($giftCodeExist){
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('Gift code has exist')
                    );
                }
                $wrappingRawData['updated_at'] = '';

                $model->addData($wrappingRawData);

                $data = new \Magento\Framework\DataObject($wrappingRawData);
                if ($data->getData('image_name/delete')) {
                    $model->setImage('');
                    // Delete temporary image if exists
                    $model->unsTmpImage();
                } else {
                    try {
                        $model->attachUploadedImage('image_name');
                    } catch (\Exception $e) {
                        throw new \Magento\Framework\Exception\LocalizedException(
                            __('You have not uploaded the image.')
                        );
                    }
                }

                $model->save();
                $this->messageManager->addSuccess(__('You saved the gift wrapping.'));
                /**
                 * function export csv report
                 */
                $this->_giftWrappingHelper->exportCsv($model);
                $redirectBack = $this->getRequest()->getParam('back', false);
                if ($redirectBack) {
                    $resultRedirect->setPath(
                        'adminhtml/*/edit',
                        ['id' => $model->getId(), 'store' => $model->getStoreId()]
                    );
                    return $resultRedirect;
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                $resultRedirect->setPath('adminhtml/*/edit', ['id' => $model->getId()]);
                return $resultRedirect;
            } catch (\Exception $e) {
                $this->messageManager->addError(__('We can\'t save the gift wrapping right now.'));
                $this->log->critical($e);
            }
        }
        $resultRedirect->setPath('adminhtml/*/');
        return $resultRedirect;
    }
}
