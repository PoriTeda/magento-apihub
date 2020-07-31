<?php

namespace Riki\SubscriptionCourse\Model;

class ImportHandler extends \Magento\ImportExport\Model\Import
{
    const ALLOWED_ERRORS_COUNT = 100;

    public function getEntity()
    {
        return 'subscription_course';
    }

    /**
     * @param null $entity
     * @return bool
     */
    public function isReportEntityType($entity = null)
    {
        return false;
    }

    /**
     * @return \Magento\ImportExport\Model\Import\AbstractEntity|\Magento\ImportExport\Model\Import\Entity\AbstractEntity
     */
    protected function _getEntityAdapter()
    {
        if (!$this->_entityAdapter) {
            $this->_entityAdapter = $this->_entityFactory->create(
                \Riki\SubscriptionCourse\Model\ImportHandler\SubscriptionCourse::class
            );
        }

        return $this->_entityAdapter;
    }
}
