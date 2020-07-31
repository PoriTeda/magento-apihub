<?php
namespace Riki\Prize\Controller\Adminhtml;

abstract class Action extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Riki_Prize::prize';

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(static::ADMIN_RESOURCE);
    }
}