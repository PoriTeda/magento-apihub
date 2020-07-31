<?php

namespace Riki\FairAndSeasonalGift\Controller\Adminhtml\Related;

class Delete extends \Riki\FairAndSeasonalGift\Controller\Adminhtml\Fair
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            $connectedId = $this->getRequest()->getParam('id');
            $connected = $this->_fairConnectionFactory->create();
            $connected->load($connectedId);
            if($connected->getId()){
                $connected->delete();
            }
            $this->messageManager->addSuccess(__('Related fair was successfully deleted'));
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }
        return $resultRedirect->setPath('fair_seasonal/fair/edit', ['fair_id' => $this->getRequest()->getParam('fair_id')]);
    }
}
