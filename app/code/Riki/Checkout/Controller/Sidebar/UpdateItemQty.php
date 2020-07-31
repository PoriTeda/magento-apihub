<?php
namespace Riki\Checkout\Controller\Sidebar;

class UpdateItemQty extends \Magento\Checkout\Controller\Sidebar\UpdateItemQty
{

    protected function jsonResponse($error = '')
    {
        $response = $this->sidebar->getResponseData($error);
        $response['type'] = 'updateQty';

        return $this->getResponse()->representJson(
            $this->jsonHelper->jsonEncode($response)
        );
    }
}
