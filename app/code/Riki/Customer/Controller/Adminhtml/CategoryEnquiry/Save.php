<?php
namespace Riki\Customer\Controller\Adminhtml\CategoryEnquiry;
use Magento\Backend\App\Action;
class Save extends Action
{
    /**
     * @var \Riki\Customer\Model\CategoryEnquiry
     *
     */
    protected $modelCategoryEnquiry;

    /**
     * Save constructor.
     * @param Action\Context $context
     * @param \Riki\Customer\Model\CategoryEnquiry $modelCategoryEnquiry
     */
    public function __construct(
        Action\Context $context,
        \Riki\Customer\Model\CategoryEnquiry $modelCategoryEnquiry
    ) {
        $this->modelCategoryEnquiry = $modelCategoryEnquiry;
        parent::__construct($context);
    }
    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Customer::category_save');
    }
    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {

        $data = $this->getRequest()->getPostValue();
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
            /** @var \Riki\Customer\Model\Category $model */
            $model = $this->modelCategoryEnquiry;

            $id = $this->getRequest()->getParam('id');
            if ($id) {
                $model->load($id);
            }

            $model->setData($data);

            $this->_eventManager->dispatch(
                'enquery_category_prepare_save',
                ['category' => $model, 'request' => $this->getRequest()]
            );

            try {
                $model->save();
                $this->messageManager->addSuccess(__('Category saved'));
                $this->_getSession()->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId(), '_current' => true]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while saving the category'));
            }

            $this->_getSession()->setFormData($data);
            return $resultRedirect->setPath('*/*/edit', ['entity_id' => $this->getRequest()->getParam('id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }
}