<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogEvent\Controller\Adminhtml\Catalog\Event;

use Magento\CatalogEvent\Model\Event as ModelEvent;
use Magento\Framework\Exception\LocalizedException;

class Save extends \Magento\CatalogEvent\Controller\Adminhtml\Catalog\Event
{
    /**
     * Filtering posted data. Converting localized data if needed
     *
     * @param array $data
     * @return array
     */
    protected function _filterPostData($data)
    {
        if (isset($data['catalogevent'])) {
            $inputFilter = new \Zend_Filter_Input(
                ['date_start' => $this->_dateTimeFilter, 'date_end' => $this->_dateTimeFilter],
                [],
                $data['catalogevent']
            );
            $data['catalogevent'] = $inputFilter->getUnescaped();
        }
        return $data;
    }

    /**
     * Save action
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        /* @var ModelEvent $event*/
        $event = $this->_eventFactory->create()->setStoreId($this->getRequest()->getParam('store', 0));
        $eventId = $this->getRequest()->getParam('id', false);
        if ($eventId) {
            $event->load($eventId);
        } else {
            $event->setCategoryId($this->getRequest()->getParam('category_id'));
        }

        $postData = $this->_filterPostData($this->getRequest()->getPostValue());

        if (!isset($postData['catalogevent'])) {
            $this->messageManager->addError(__('Something went wrong while saving this event.'));
            $this->_redirect('adminhtml/*/edit', ['_current' => true]);
            return;
        }

        $data = new \Magento\Framework\DataObject($postData['catalogevent']);

        /** @var \Magento\CatalogEvent\Model\DateResolver $dateResolver */
        $dateResolver = $this->_objectManager->get(\Magento\CatalogEvent\Model\DateResolver::class);

        $event->setDisplayState(
            $data->getDisplayState()
        )->setStoreDateStart(
            $dateResolver->convertDate($data->getDateStart())
        )->setStoreDateEnd(
            $dateResolver->convertDate($data->getDateEnd())
        )->setSortOrder(
            $data->getSortOrder()
        )->applyStatusByDates();

        $isUploaded = true;
        try {
            $uploader = $this->_objectManager->create(
                \Magento\MediaStorage\Model\File\Uploader::class,
                ['fileId' => 'image']
            );
            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setAllowCreateFolders(true);
            $uploader->setFilesDispersion(false);
        } catch (\Exception $e) {
            $isUploaded = false;
        }

        $validateResult = $event->validate();
        if ($validateResult !== true) {
            foreach ($validateResult as $errorMessage) {
                $this->messageManager->addError($errorMessage);
            }
            $this->_getSession()->setEventData($event->getData());
            $this->_redirect('adminhtml/*/edit', ['_current' => true]);
            return;
        }

        try {
            if ($data->getData('image/is_default')) {
                $event->setImage(null);
            } elseif ($data->getData('image/delete')) {
                $event->setImage('');
            } elseif ($isUploaded) {
                try {
                    $event->setImage($uploader);
                } catch (\Exception $e) {
                    throw new LocalizedException(__('We did not upload your image.'));
                }
            }
            $event->save();

            $this->messageManager->addSuccess(__('You saved the event.'));
            if ($this->getRequest()->getParam('back') == 'edit') {
                $this->_redirect('adminhtml/*/edit', ['_current' => true, 'id' => $event->getId()]);
            } else {
                $this->_redirect('adminhtml/*/');
            }
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
            $this->_getSession()->setEventData($event->getData());
            $this->_redirect('adminhtml/*/edit', ['_current' => true]);
        }
    }
}
