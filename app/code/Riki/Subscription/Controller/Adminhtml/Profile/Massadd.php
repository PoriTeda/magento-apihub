<?php

namespace Riki\Subscription\Controller\Adminhtml\Profile;

class Massadd extends Add
{
    /**
     * @return mixed
     */
    public function execute()
    {
        $isAdditional = $this->getRequest()->getParam('is_additional', 0);
        $objProfileCache = $this->getProfileCache();
        if (!$objProfileCache) {
            $resultData['message'] = __('There are something wrong in the system. Please re-try again');
            $result = $this->_controllerHelper->getResultJson()->create();
            return $result->setData($resultData);
        }
        $addResult = $this->_controllerHelper->addProductsToProfile(
            $this->getRequest()->getParam('id', 0),
            $this->prepareItemsData(),
            $objProfileCache,
            $isAdditional
        );

        $errorMessage = count($addResult['error_messages']) ? implode("\n", $addResult['error_messages']) : null;

        /** @var \Magento\Framework\Controller\Result\Json $result */
        $result = $this->_controllerHelper->getResultJson()->create();

        $resultData = [
            'success' => $addResult['success']
        ];

        if ($errorMessage) {
            $resultData['message'] = $errorMessage;
        }
        $this->saveToCache($objProfileCache);
        return $result->setData($resultData);
    }

    /**
     * @return array
     */
    protected function prepareItemsData()
    {
        $addProducts = [];

        $requestItemsData = $this->getRequest()->getParam('items', []);

        foreach ($requestItemsData as $productId =>  $addProduct) {
            $addProduct['product_id'] = $productId;
            $addProducts[] = $addProduct;
        }

        $addProducts = array_map(function($itemData) {

            $fields = [
                'product_id'    =>  0,
                'qty'   =>  1,
                'unit_case' =>  \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_PIECE,
                'unit_qty'  =>  1
            ];

            foreach ($fields as $field  =>  $default) {
                $itemData[$field] = isset($itemData[$field]) ? $itemData[$field] : $default;
            }

            $itemData['unit_case'] = strtoupper($itemData['unit_case']);

            if ($itemData['unit_case'] == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
                $itemData['qty'] = max($itemData['unit_qty'], 1) * $itemData['qty'];
            }

            return $itemData;

        }, $addProducts);



        return $addProducts;
    }
}