<?php
namespace Riki\Wamb\Controller\Adminhtml\Rule;

class NewAction extends \Riki\Wamb\Controller\Adminhtml\Rule
{
    const ADMIN_RESOURCE = 'Riki_Wamb::Rule_save';

    /**
     * {@inheritdoc}
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        return $this->initForwardResult()->forward('edit');
    }
}
