<?php
namespace Riki\CatalogRule\Controller\Adminhtml\Wbs;
class NewAction extends \Riki\CatalogRule\Controller\Adminhtml\Wbs\WbsAction
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        return $this->resultForwardFactory->create()->forward('edit');
    }
}
