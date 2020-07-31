<?php
namespace Riki\Fraud\Controller\Adminhtml\Rule;
use Mirasvit\FraudCheck\Controller\Adminhtml\Rule;
class Duplicate extends Rule
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data = $this->getRequest()->getParams()) {
            $model = $this->initModel();
            $model->setRuleId(null);
            try {
                $model->save();
                $this->messageManager->addSuccess(__('Item was successfully duplicated.'));
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        } else {
            $this->messageManager->addError(__('Unable to find item to save'));
        }
        return $resultRedirect->setPath('fraud_check/rule');
    }
}
