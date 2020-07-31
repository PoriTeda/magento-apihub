<?php
namespace Riki\ShipLeadTime\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Riki\ShipLeadTime\Model\Leadtime as Leadtime;
use Magento\Backend\Model\Session;
use Riki\ShipLeadTime\Model\ResourceModel\Leadtime\CollectionFactory as LeadtimeCollectionFactory;

class Save extends \Magento\Backend\App\Action
{
    /* Riki\ShipLeadTime\Model\Leadtime */
    protected $leadTime;

    /* Magento\Backend\Model\Session */
    protected $session;

    /* Riki\ShipLeadTime\Model\ResourceModel\Leadtime\CollectionFactory */
    protected $leadTimeCollectionFactory;
    /**
     * @param Action\Context $context
     */
    public function __construct(
        LeadtimeCollectionFactory $leadTimeCollectionFactory,
        Leadtime $leadTime,
        Action\Context $context
    ){
        $this->leadTimeCollectionFactory = $leadTimeCollectionFactory;
        $this->session = $context->getSession();
        $this->leadTime = $leadTime;
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_ShipLeadTime::shipleadtime_edit');
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
            $model = $this->leadTime;

            $id = $this->getRequest()->getParam('id');
            if ($id) {
                $model->load($id);
                if ($model->getPrefId() != $data['pref_id']
                    || $model->getWarehouseId() != $data['warehouse_id']
                    || $model->getDeliveryTypeCode() != $data['delivery_type_code']) {
                    if ($this->checkRowIsExist($data)) {
                        $this->messageManager->addError(__('This shipping lead time is exist.'));
                        $this->_getSession()->setFormData($data);
                        $this->_redirect('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
                        return;
                    }
                }
                //check lead time priority
                if($this->checkPriorityExist($data, $id))
                {
                    $this->messageManager->addError(__('This priority for lead time is exist.'));
                    $this->_getSession()->setFormData($data);
                    $this->_redirect('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
                    return;
                }

            } else {
                // Check row is exist
                if ($this->checkRowIsExist($data)) {
                    $this->messageManager->addError(__('This shipping lead time is exist.'));
                    $this->_getSession()->setFormData($data);
                    $this->_redirect('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
                    return;
                }
                //check lead time priority
                if($this->checkPriorityExist($data))
                {
                    $this->messageManager->addError(__('This priority for lead time is exist.'));
                    $this->_getSession()->setFormData($data);
                    $this->_redirect('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
                    return;
                }

            }

            $model->addData($data);
            if (!$data['priority']) {
                $model->setPriority(NULL);
            }
            try {
                $model->save();
                $this->messageManager->addSuccess(__('The Shipping Lead Time has been saved.'));
                $this->session->setFormData(false);
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

    /**
     * @param $data
     * @return bool
     */
    public function checkRowIsExist($data)
    {
        $collectionFactory = $this->leadTimeCollectionFactory->create()->addFieldToFilter('pref_id', $data['pref_id'])
                                                             ->addFieldToFilter('warehouse_id', $data['warehouse_id'])
                                                             ->addFieldToFilter('delivery_type_code', $data['delivery_type_code']);
        if ($collectionFactory->getSize() > 0) {
            return true;
        }

        return false;
    }
    /**
     * @param $data
     * @param null $leadtimeId
     * @return bool
     */
    public function checkPriorityExist($data, $leadtimeId = null)
    {
        $collectionFactory = $this->leadTimeCollectionFactory->create()->addFieldToFilter('pref_id', $data['pref_id'])
            ->addFieldToFilter('delivery_type_code', $data['delivery_type_code'])
            ->addFieldToFilter('priority', $data['priority']);
        if($leadtimeId) {
            $collectionFactory->addFieldToFilter('id', ['neq'=> $leadtimeId]);
        }
        if ($collectionFactory->getItems()) {
            return true;
        }
        return false;
    }
}
