<?php

namespace Riki\TimeSlots\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Riki\TimeSlots\Model\TimeSlots;
use Riki\TimeSlots\Model\ResourceModel\TimeSlots\CollectionFactory;


class Save extends \Magento\Backend\App\Action
{
    /* @var \Riki\TimeSlots\Model\TimeSlots */
    protected $_timeSlots;

    /* @var \Magento\Backend\Model\Session */
    protected $_backendSession;

    /* @var \Riki\TimeSlots\Model\ResourceModel\TimeSlots\CollectionFactory */
    protected $_timeSlotCollectionFactory;

    /**
     * Construct
     *
     * @param \Riki\TimeSlots\Model\ResourceModel\TimeSlots\CollectionFactory $collectionFactory
     * @param \Riki\TimeSlots\Model\TimeSlots $timeSlotsModel
     * @param Action\Context $context
     */
    public function __construct(
        \Riki\TimeSlots\Model\ResourceModel\TimeSlots\CollectionFactory $collectionFactory,
        Action\Context $context,
        \Riki\TimeSlots\Model\TimeSlots $timeSlotsModel
    ){
        $this->_timeSlotCollectionFactory = $collectionFactory;
        $this->_backendSession = $context->getSession();
        $this->_timeSlots = $timeSlotsModel;
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_TimeSlots::manage_time_slots_save');
    }

    /**
     * Save action
     *
     * @return void
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        // Check From To
        if($this->validFromTo($data)){
            $this->messageManager->addError(__('To time must greater than From time'));
            $this->_getSession()->setFormData($data);
            $this->_redirect('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
            return;
        }

        if ($data) {
            $id = $this->getRequest()->getParam('id');
            $model = $this->_timeSlots;
            if ($id) {
                $model = $this->_timeSlots->load($id);
                if($model->getAppointedTimeSlot() != $data['appointed_time_slot']) {
                    if($this->checkAppointedTimeSlotExist($data)) {
                        $this->messageManager->addError(__('This appointed time slot is exist'));
                        $this->_getSession()->setFormData($data);
                        $this->_redirect('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
                        return;
                    }
                }
            } else {
                // Check Appointed Time Slot is exist
                if($this->checkAppointedTimeSlotExist($data)) {
                    $this->messageManager->addError(__('This appointed time slot is exist'));
                    $this->_getSession()->setFormData($data);
                    $this->_redirect('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
                    return;
                }
            }


            $model->addData($data);
            try {
                $model->save();
                $this->messageManager->addSuccess(__('The time slots has been saved.'));
                $this->_backendSession->setFormData(false);
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
                $this->messageManager->addException($e, __('Something went wrong while saving the time slots.'.$e->getMessage()));
            }

            $this->_getSession()->setFormData($data);
            $this->_redirect('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
            return;
        }
        $this->_redirect('*/*/');
    }

    public function checkAppointedTimeSlotExist($data)
    {
        $collectionFactory = $this->_timeSlotCollectionFactory->create()
            ->addFieldToFilter('appointed_time_slot', $data['appointed_time_slot']);
        if ($collectionFactory->getSize() > 0) {
            return true;
        }
        return false;
    }

    public function validFromTo($data)
    {
        if (isset($data['from']) && isset($data['to'])) {
            if ($data['from'] > $data['to']) {
                return true;
            }
        }
        return false;
    }
}
