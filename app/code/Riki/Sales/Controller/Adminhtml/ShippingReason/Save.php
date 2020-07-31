<?php

namespace Riki\Sales\Controller\Adminhtml\ShippingReason;

use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Message\Manager;
use Magento\Framework\Api\DataObjectHelper;
use Riki\Sales\Api\ShippingReasonRepositoryInterface;
use Riki\Sales\Api\Data\ShippingReason\ShippingReasonInterface;
use Riki\Sales\Api\Data\ShippingReason\ShippingReasonInterfaceFactory;
use Riki\Sales\Controller\Adminhtml\ShippingReason\Reason;
use Riki\Sales\Model\ShippingReasonData;

class Save extends Reason
{
    /**
     * @var Manager
     */
    protected $messageManager;

    /**
     * @var ShippingReasonRepositoryInterface
     */
    protected $shippingReasonRepository;

    /**
     * @var ShippingReasonInterfaceFactory
     */
    protected $shippingReasonFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    public function __construct(
        Registry $registry,
        ShippingReasonRepositoryInterface $shippingReasonRepository,
        PageFactory $resultPageFactory,
        ForwardFactory $resultForwardFactory,
        Manager $messageManager,
        ShippingReasonInterfaceFactory $shippingReasonFactory,
        DataObjectHelper $dataObjectHelper,
        Context $context
    ) {
        $this->messageManager   = $messageManager;
        $this->shippingReasonFactory      = $shippingReasonFactory;
        $this->shippingReasonRepository   = $shippingReasonRepository;
        $this->dataObjectHelper  = $dataObjectHelper;
        parent::__construct($registry, $shippingReasonRepository, $resultPageFactory, $resultForwardFactory, $context);
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
                $model = $this->shippingReasonRepository->getById($id);
            } else {
                unset($data['id']);
                $model = $this->shippingReasonFactory->create();
            }
            try {
                $this->dataObjectHelper->populateWithArray($model, $data, ShippingReasonInterface::class);
                $this->shippingReasonRepository->save($model);
                $this->messageManager->addSuccessMessage(__('You saved this Shipping Reason successfully.'));
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
                $this->messageManager->addException($e, __('Something went wrong while saving the Shipping Reason.'));
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
        return $this->_authorization->isAllowed(ShippingReasonData::ACL_SAVE);
    }
}
