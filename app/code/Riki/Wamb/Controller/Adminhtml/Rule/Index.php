<?php


namespace Riki\Wamb\Controller\Adminhtml\Rule;

class Index extends \Riki\Wamb\Controller\Adminhtml\Rule
{
    const ADMIN_RESOURCE = 'Riki_Wamb::Rule_view';

    /**
     * {@inheritdoc}
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultPage = $this->initPageResult();
        $resultPage->addBreadcrumb(__('Manage WAMB Rules '), __('Manage WAMB Rules'));
        $resultPage->getConfig()->getTitle()->prepend(__('Manage WAMB Rules'));

        return $resultPage;
    }
}
