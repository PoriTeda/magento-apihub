<?php
namespace Riki\CatalogRule\Model\ResourceModel\Grid;

class Collection extends \Magento\CatalogRule\Model\ResourceModel\Grid\Collection
{
    /**
     * @return $this
     */
    protected function _initSelect()
    {
        $this->getSelect()->from(['main_table' => $this->getMainTable()])->where('is_machine != ?', 1);
        $this->addWebsitesToResult();

        return $this;
    }
}
