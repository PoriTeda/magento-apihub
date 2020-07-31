<?php
namespace Riki\DeliveryType\Controller\Adminhtml\Delitype;

use Magento\Backend\App\Action;

class Save extends \Magento\Backend\App\Action
{

    /**
     * @var \Riki\DeliveryType\Model\Delitype
     */
    protected $_delitypeModel;

    /**
     * @param Action\Context $context
     */
    public function __construct(
        Action\Context $context,
        \Riki\DeliveryType\Model\Delitype $delitypeModel
    )
    {
        $this->_delitypeModel = $delitypeModel;

        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_DeliveryType::managedelitype');
    }

    /**
     * Save action
     *
     * @return void
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            $model = $this->_delitypeModel;

            $id = $this->getRequest()->getParam('id');
            if ($id) {
                $model->load($id);
            }
            $model->addData($data);

            try {
                $model->save();
                $this->messageManager->addSuccess(__('The Delivery Type has been saved.'));
                $this->_session->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', ['id' => $model->getId(), '_current' => true]);
                    return;
                }
                $this->_redirect('*/*/');
                return;
            } catch (\Magento\Framework\Model\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while saving the shipping lead time.'));
            }

            $this->_getSession()->setFormData($data);
            $this->_redirect('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
            return;
        }
        $this->_redirect('*/*/');
    }
}
