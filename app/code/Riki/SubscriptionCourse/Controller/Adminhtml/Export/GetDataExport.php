<?php
namespace Riki\SubscriptionCourse\Controller\Adminhtml\Export;

class GetDataExport extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = 'sales'
    ){
        parent::__construct($context, $connectionName);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('subscription_course', 'course_id');
    }

    public function getDataExportByTableName($tableName)
    {
        $i = 0;
        $arrExportData = array();
        $arrTmpValue = array();
        $subCategorySelect = $this->getConnection('sales')->select()
            ->from($this->getTable($tableName));
        $subCategoryCollection  = $this->getConnection('sales')->fetchAll($subCategorySelect);
        foreach ($subCategoryCollection as $item) {
            foreach ($item as $key => $value) {
                if ($i == 0) {
                    $arrExportData[$i][] = $key;
                    $arrTmpValue[] = $value;
                } else {
                    $arrExportData[$i][] = $value;
                }
            }
            if ($i == 0) {
                $i = 1;
                $arrExportData[$i] = $arrTmpValue;
            }
            $i++;
        }
        return $arrExportData;
    }
}