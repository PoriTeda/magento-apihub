<?php

namespace Wyomind\PointOfSale\Controller\Adminhtml\Manage;

/**
 * Delete action
 */
class Delete extends \Wyomind\PointOfSale\Controller\Adminhtml\PointOfSale
{

    /**
     * Execute action
     * @return void
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        if (!is_array($id)) {
            $id = [$id];
        }
        if (count($id) > 0) {
            try {
                $model = $this->_objectManager->create('Wyomind\PointOfSale\Model\PointOfSale');

                $collection = $model->getPlaces();
                $collection->addFieldToFilter('place_id', ['in' => $id]);

                foreach ($collection as $pos) {
                    $name = $pos->getName();
                    $pos->delete();
                    $this->messageManager->addSuccess(__('%1 has been deleted.', $name));
                }
                $result = $this->_resultRedirectFactory->create()->setPath('pointofsale/manage/index');
                return $result;
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $result = $this->_resultRedirectFactory->create()->setPath('pointofsale/manage/index', []);
                return $result;
            }
        }
        $this->messageManager->addError(__("We can't find a POS / WH to delete."));
        $result = $this->_resultRedirectFactory->create()->setPath('pointofsale/manage/index');
        return $result;
    }
}
