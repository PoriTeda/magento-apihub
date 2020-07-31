<?php

namespace Wyomind\PointOfSale\Controller\Adminhtml\Manage;

/**
 * Edit action
 */
class Edit extends \Wyomind\PointOfSale\Controller\Adminhtml\PointOfSale
{

    /**
     * Execute action
     * @return void
     */
    public function execute()
    {

        $resultPage = $this->_resultPageFactory->create();
        $resultPage->setActiveMenu("Magento_Sales::sales");
        $resultPage->addBreadcrumb(__('Point Of Sale'), __('Point Of Sale'));
        $resultPage->addBreadcrumb(__('Manage POS / WH'), __('Manage POS / WH'));

        $id = $this->getRequest()->getParam('id');
        $model = $this->_objectManager->create('Wyomind\PointOfSale\Model\PointOfSale');

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This point of sale / Warehouse no longer exists.'));
                $this->_redirect('pointofsale/manage/');
                return;
            }
        }

        $resultPage->getConfig()->getTitle()->prepend($model->getPlaceId() ? (__('Modify POS / WH : ') . $model->getName()) : __('New POS /WH'));


        $this->_coreRegistery->register('pointofsale', $model);

       
        return $resultPage;
    }
}
