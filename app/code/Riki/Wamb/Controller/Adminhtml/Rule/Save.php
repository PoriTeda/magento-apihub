<?php
namespace Riki\Wamb\Controller\Adminhtml\Rule;

class Save extends \Riki\Wamb\Controller\Adminhtml\Rule
{
    const ADMIN_RESOURCE = 'Riki_Wamb::Rule_save';

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->initRedirectResult();
        $postValues = $data = $this->getRequest()->getPostValue();
        if(isset($postValues['category_ids'])){
            $postValues['data_category'] = \Zend_Json::encode($postValues['category_ids']);
        }
        if(isset($data['course_ids'])){
            $postValues['data_course'] = \Zend_Json::encode($postValues['course_ids']);
        }

        $id = isset($postValues['rule_id']) ? $postValues['rule_id'] : 0;
        try {
            $rule = $this->ruleRepository->getById($id);
            $rule->addData($postValues);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $rule = $this->ruleRepository->createFromArray($postValues);
        } catch (\Exception $e) {
            $this->messageManager->addError(__('An error occurred, please try again!'));
            $this->logger->critical($e);
        }

        if (!isset($rule)) {
            $this->_session->setFormData($postValues);
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $this->ruleRepository->save($rule);
            $this->messageManager->addSuccess(__('Your changes have been updated successfully.'));
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->_session->setFormData($postValues);
            $this->messageManager->addErrorMessage($e->getMessage());
            $resultRedirect->setPath('*/*/edit', ['id' => $rule->getRuleId()]);
            $this->logger->warning($e);
        } catch (\Exception $e) {
            $this->_session->setFormData($postValues);
            $this->messageManager->addError(__('An error occurred, please try again!'));
            $this->logger->critical($e);
        }

        $redirectBack = $this->getRequest()->getParam('back', false);
        return $redirectBack?
            $resultRedirect->setPath('*/*/edit', ['id' => $rule->getId()])
            : $resultRedirect->setPath('*/*/');
    }


}
