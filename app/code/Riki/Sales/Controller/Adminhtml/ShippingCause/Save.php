<?php

namespace Riki\Sales\Controller\Adminhtml\ShippingCause;

use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Message\Manager;
use Magento\Framework\Api\DataObjectHelper;
use Riki\Sales\Api\ShippingCauseRepositoryInterface;
use Riki\Sales\Api\Data\ShippingCause\ShippingCauseInterface;
use Riki\Sales\Api\Data\ShippingCause\ShippingCauseInterfaceFactory;
use Riki\Sales\Controller\Adminhtml\ShippingCause\Cause;
use Riki\Sales\Model\ShippingCauseData;

class Save extends Cause
{
    /**
     * @var Manager
     */
    protected $messageManager;

    /**
     * @var ShippingCauseRepositoryInterface
     */
    protected $shippingCauseRepository;

    /**
     * @var ShippingCauseInterfaceFactory
     */
    protected $shippingCauseFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    public function __construct(
        Registry $registry,
        ShippingCauseRepositoryInterface $shippingCauseRepository,
        PageFactory $resultPageFactory,
        ForwardFactory $resultForwardFactory,
        Manager $messageManager,
        ShippingCauseInterfaceFactory $shippingCauseFactory,
        DataObjectHelper $dataObjectHelper,
        Context $context
    ) {
        $this->messageManager   = $messageManager;
        $this->shippingCauseFactory      = $shippingCauseFactory;
        $this->shippingCauseRepository   = $shippingCauseRepository;
        $this->dataObjectHelper  = $dataObjectHelper;
        parent::__construct($registry, $shippingCauseRepository, $resultPageFactory, $resultForwardFactory, $context);
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
            $id = $this->getRequest()->getParam('id');
            if ($id) {
                $model = $this->shippingCauseRepository->getById($id);
            } else {
                unset($data['id']);
                $model = $this->shippingCauseFactory->create();
            }

            try {
                $this->dataObjectHelper->populateWithArray($model, $data, ShippingCauseInterface::class);
                $this->shippingCauseRepository->save($model);
                $this->messageManager->addSuccessMessage(__('You saved this Shipping Cause successfully.'));
                $this->_getSession()->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId(), '_current' => true]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while saving the Shipping Cause.'));
            }

            $this->_getSession()->setFormData($data);
            return $resultRedirect->setPath('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }
    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(ShippingCauseData::ACL_SAVE);
    }
}
