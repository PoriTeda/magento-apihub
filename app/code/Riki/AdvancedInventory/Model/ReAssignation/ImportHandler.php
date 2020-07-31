<?php
namespace Riki\AdvancedInventory\Model\ReAssignation;

class ImportHandler extends \Magento\ImportExport\Model\Import
{
    protected $_debugMode = false;

    const ALLOWED_ERRORS_COUNT = 100;

    public function getEntity()
    {
        return 'order';
    }

    /**
     * Retrieve processed reports entity types
     *
     * @param string|null $entity
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isReportEntityType($entity = null)
    {
        return false;
    }

    /**
     * @return bool
     */
    protected function processImport()
    {

        $this->_getEntityAdapter()->setUsername($this->getData(\Riki\AdvancedInventory\Model\ReAssignation\ImportHandler\ReAssignation::UPLOADED_BY));

        parent::processImport();
    }

    /**
     * @return \Magento\ImportExport\Model\Import\AbstractEntity|\Magento\ImportExport\Model\Import\Entity\AbstractEntity
     */
    protected function _getEntityAdapter()
    {
        if (!$this->_entityAdapter) {
            $this->_entityAdapter = $this->_entityFactory->create(\Riki\AdvancedInventory\Model\ReAssignation\ImportHandler\ReAssignation::class);
        }

        return $this->_entityAdapter;
    }
}