<?php
namespace Riki\CatalogRule\Controller\Adminhtml\Wbs;

use Magento\Framework\Stdlib\DateTime;

class Edit extends \Riki\CatalogRule\Controller\Adminhtml\Wbs\WbsAction
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $resultPage = $this->resultFactory->create(
            \Magento\Framework\Controller\ResultFactory::TYPE_PAGE
        );

        $id = $this->getRequest()->getParam('entity_id');

        $model = $this->initModel();

        if ($id && !$model->getId()) {
            $this->messageManager->addError(__('This item not exists.'));
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $model = $this->convertModelDataToFormData($model);

        $this->initPage($resultPage)->getConfig()->getTitle()->prepend(
            $id ? __('Edit Wbs Conversion Rule') : __('Add Wbs Conversion Rule')
        );

        $values = $this->_getSession()->getData('riki_wbs_conversion_data', true);

        if (!empty($values)) {
            $model->addData($values);
        }

        $this->registry->register('current_wbs_conversion_form', $model);

        return $resultPage;
    }

    /**
     * convert model data to form data
     *
     * @param $model
     * @return mixed
     */
    public function convertModelDataToFormData($model)
    {
        if ($model->getId()) {

            $fromDatetime = $this->convertDateTimeToConfigTimezone($model->getData('from_datetime'));

            /*set data for Start conversion date and time section*/
            $model->setData('from_date', $fromDatetime->format('Y-m-d'));
            $model->setData('from_time', $fromDatetime->format('H:i:s'));

            $toDatetime = $this->convertDateTimeToConfigTimezone($model->getData('to_datetime'));

            /*set data for End conversion date and time section*/
            $model->setData('to_date', $toDatetime->format('Y-m-d'));
            $model->setData('to_time', $toDatetime->format('H:i:s'));
        }

        return $model;
    }

    /**
     * convert date time to config timezone
     *
     * @param $datetime
     * @return DateTime
     */
    public function convertDateTimeToConfigTimezone($datetime)
    {
        $date = $this->timezone->date($datetime, null, false);

        $date->setTimezone(
            new \DateTimeZone($this->timezone->getConfigTimezone())
        );

        return $date;
    }
}
