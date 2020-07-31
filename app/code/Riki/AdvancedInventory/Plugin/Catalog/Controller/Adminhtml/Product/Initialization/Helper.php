<?php

namespace Riki\AdvancedInventory\Plugin\Catalog\Controller\Adminhtml\Product\Initialization;

class Helper
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    protected $permissionsHelper;

    protected $stockModel;

    /**
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Wyomind\AdvancedInventory\Model\Stock $stockModel,
        \Wyomind\AdvancedInventory\Helper\Permissions $permissionsHelper
    )
    {
        $this->request = $request;
        $this->stockModel = $stockModel;
        $this->permissionsHelper = $permissionsHelper;
    }

    /**
     * check warehouse qty with general qty
     *
     * @param \Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper $subject
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    public function beforeInitialize(
        \Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper $subject,
        \Magento\Catalog\Model\Product $product
    ) {

        $postData = $this->request->getPostValue();

        $productData = $postData['product'];

        if (
            isset($postData['inventory']) &&
            isset($postData['inventory']['multistock']) &&
            $postData['inventory']['multistock'] == 1
        ) {

            $hasAllPermission = $this->permissionsHelper->hasAllPermissions();

            if (isset($postData['inventory']['pos_wh'])) {
                $whs = (array)$postData['inventory']['pos_wh'];
            } else {
                $whs = [];
            }

            $stockModel = $this->stockModel->getStockSettings($this->request->getParam('id'), false, array_keys($whs));

            $finalQty = 0;
            $whsQty = 0;
            foreach ($whs as $whId => $whData) {
                if (isset($postData['store_id']) || !$hasAllPermission) {
                    $getQtyMethodName = 'getQuantity' . $whId;
                    $whsQty += $stockModel->$getQtyMethodName();
                }
                $finalQty += $whData['qty'];
            }

            if (isset($postData['store_id']) || !$hasAllPermission) {
                $finalQty = $stockModel->getQty() - $whsQty + $finalQty;
            }

            $productData['quantity_and_stock_status']['qty'] = $finalQty;
            $productData['stock_data']['qty'] = $finalQty;

            $this->request->setPostValue('product', $productData);
        }

        return [$product];
    }
}
