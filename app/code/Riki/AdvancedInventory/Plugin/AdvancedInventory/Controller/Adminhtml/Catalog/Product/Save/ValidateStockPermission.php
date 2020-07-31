<?php
namespace Riki\AdvancedInventory\Plugin\AdvancedInventory\Controller\Adminhtml\Catalog\Product\Save;

class ValidateStockPermission
{
    /**
     * @var \Wyomind\AdvancedInventory\Helper\Permissions
     */
    protected $permissionsHelper;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * ValidateStockPermission constructor.
     * @param \Wyomind\AdvancedInventory\Helper\Permissions $permissionsHelper
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        \Wyomind\AdvancedInventory\Helper\Permissions $permissionsHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->permissionsHelper = $permissionsHelper;
        $this->messageManager = $messageManager;
    }

    /**
     * @param \Magento\Catalog\Controller\Adminhtml\Product\Save $subject
     * @return array
     */
    public function beforeExecute(
        \Magento\Catalog\Controller\Adminhtml\Product\Save $subject
    ) {
        if ($subject->getRequest()->getParam('id') == null) {
            return [];
        }

        $isStoreMode = $subject->getRequest()->getParam('store_id');
        $hasAllPermission = $this->permissionsHelper->hasAllPermissions();

        $postData = $subject->getRequest()->getParams();

        if (isset($postData['inventory'])
            && isset($postData['inventory']['multistock'])
            && $postData['inventory']['multistock']
            && ($isStoreMode || !$hasAllPermission)
        ) {
            if ($isStoreMode) {
                $this->messageManager->addWarning(__('Stock data has not been updated in store mode.'));
            }

            if (!$hasAllPermission) {
                $this->messageManager->addWarning(__('You don\'t have permission to update stock data.'));
            }

            unset($postData['product']['stock_data']);
            unset($postData['product']['quantity_and_stock_status']);

            $subject->getRequest()->setPostValue('inventory', null);
            $subject->getRequest()->setPostValue('product', $postData['product']);
        }

        return [];
    }
}