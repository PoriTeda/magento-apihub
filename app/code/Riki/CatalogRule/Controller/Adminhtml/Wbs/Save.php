<?php

namespace Riki\CatalogRule\Controller\Adminhtml\Wbs;

use Magento\Framework\Exception\LocalizedException;

class Save extends \Riki\CatalogRule\Controller\Adminhtml\Wbs\WbsAction
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        $data = $this->getRequest()->getParams();

        if (!empty($data) && !empty($data['wbs'])) {

            $model = $this->initModel();

            $modelData = $this->convertPostDataToModelData($data['wbs']);

            $model->addData($modelData);

            try {

                $valid = true;

                if ($model->getOldWbs() == $model->getNewWbs()) {
                    $this->messageManager->addError(__('New Wbs code "%1" is invalid, same with old Wbs code.', $model->getNewWbs()));
                    $valid = false;
                }

                $validateUniqueFair = $this->validateUniqueFair($model);

                if (!$validateUniqueFair) {
                    $this->messageManager->addError(__('Old Wbs code "%1" already exist.', $model->getOldWbs()));
                    $valid = false;
                }

                $validateDate = $this->validateDateRange($model->getFromDatetime(),$model->getToDatetime());

                if (!$validateDate) {
                    $this->messageManager->addError(__('End date must be later than start date.'));
                    $valid = false;
                }

                if (!$valid) {
                    throw new LocalizedException(__('Unable to save this item.'));
                }

                $model->save();

                $this->messageManager->addSuccess(__('Item was saved successfully.'));

                if ($this->getRequest()->getParam('back') == 'edit') {
                    return $resultRedirect->setPath('*/*/edit', ['entity_id' => $model->getId()]);
                }

                return $resultRedirect->setPath('*/*/');

            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $this->_getSession()->setData('riki_wbs_conversion_data', $modelData);
                return $resultRedirect->setPath('*/*/edit', ['entity_id' => $model->getId()]);
            }
        } else {
            $this->messageManager->addError(__('Unable to find item to save'));
            return $resultRedirect->setPath('*/*/');
        }
    }

    public function convertPostDataToModelData($data)
    {
        $fromTime = \Riki\CatalogRule\Model\WbsConversion::DEFAULT_TIME;

        $toTime = \Riki\CatalogRule\Model\WbsConversion::DEFAULT_TIME;

        if (!empty($data['from_time']) && is_array($data['from_time'])) {
            $fromTime = implode(':', $data['from_time']);
        }

        if (!empty($data['to_time']) && is_array($data['to_time'])) {
            $toTime = implode(':', $data['to_time']);
        }

        /*replace array from post data by string*/
        $data['from_time'] = $fromTime;
        /*replace array from post data by string*/
        $data['to_time'] = $toTime;

        /*generate value for from_datetime column - base on UTC timezone*/
        $data['from_datetime'] = $this->getUtcDateTime($data['from_date'], $fromTime);

        /*generate value for to_datetime column - base on UTC timezone*/
        $data['to_datetime'] = $this->getUtcDateTime($data['to_date'], $toTime);

        return $data;
    }

    /**
     * @param $startDate
     * @param $endDate
     * @return bool
     */
    public function validateDateRange($startDate, $endDate)
    {
        $start = $this->dateTime->timestamp($startDate);
        $end = $this->dateTime->timestamp($endDate);
        if($start > $end){
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param $model
     * @return bool
     */
    public function validateUniqueFair($model)
    {
        $collection = $this->wbsConversionFactory->create()->getCollection();
        $collection->addFieldToFilter('old_wbs', $model->getOldWbs());

        if ($model->getId()) {
            $collection->addFieldToFilter('entity_id', ['neq' => $model->getId()]);
        }

        if ($collection->getSize()) {
            return false;
        }

        return true;
    }

    /**
     * Get datetime based on UTC timezone
     *
     * @param $date
     * @param $time
     * @return string
     */
    public function getUtcDateTime($date, $time)
    {
        $datetime = $date . ' ' .$time;

        $date = new \DateTime(
            $datetime,
            new \DateTimeZone($this->timezone->getConfigTimezone())
        );

        $date->setTimezone(
            new \DateTimeZone($this->timezone->getDefaultTimezone() ?: 'UTC')
        );

        return $date->format('Y-m-d H:i:s');
    }
}
