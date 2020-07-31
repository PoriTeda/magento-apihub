<?php


namespace Riki\Wamb\Controller\Adminhtml\Rule;

class Delete extends \Riki\Wamb\Controller\Adminhtml\Rule
{
    const ADMIN_RESOURCE = 'Riki_Wamb::Rule_delete';


    /**
     * {@inheritdoc}
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->initRedirectResult();

        try {
            $this->ruleRepository->deleteById($this->getRequest()->getParam('id', 0));
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->logger->warning($e);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred, please try again!'));
            $this->logger->critical($e);
        }

        return $resultRedirect->setPath('*/*/');
    }
}
