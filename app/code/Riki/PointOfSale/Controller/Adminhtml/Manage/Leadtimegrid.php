<?php
namespace Riki\PointOfSale\Controller\Adminhtml\Manage;

class Leadtimegrid extends \Wyomind\PointOfSale\Controller\Adminhtml\PointOfSale
{

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $whId = $this->getRequest()->getParam('id');

        if($whId){
            $model = $this->_posModelFactory->create()->load($whId);

            if($model && $model->getId()){
                $this->_coreRegistery->register('pointofsale', $model);
            }else{
                return null;
            }
        }

        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE);
    }
}