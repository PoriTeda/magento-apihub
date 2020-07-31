<?php

namespace Riki\SpotOrderApi\Helper;

class HandleMessageApi extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @param $string
     * @param array $arrPattern
     * @return bool
     */
    public function checkMessageError($string, $arrPattern)
    {
        $pattern = '(' . implode('|', $arrPattern) . ')';

        if (preg_match_all($pattern, $string)) {
            return true;
        }

        return false;
    }

    /**
     * Handle message for authorized,out of stock,others
     *
     * @param $message
     * @param $fileError
     * @return mixed
     */
    public function handleMessage($message, $fileError)
    {
        /**
         * List file for process paygent authorized
         */
        $arrAuthorizedFail = [
            'AuthorizeAfterAssignationSuccess',
            'Paygent'
        ];

        /**
         * List file for process message out of stock
         */
        $arrOutOfStock = [
            'Assignation',
            'ConvertToNormal',
            'Data',
            'Quote.php'
        ];

        $arrMessage['message'] = $message;
        $arrMessage['parameters'] = ['Others'];
        if ($this->checkMessageError($fileError, $arrAuthorizedFail)) {
            $arrMessage['parameters'] = ['paygent_authorized_failure'];
        } else if ($this->checkMessageError($fileError, $arrOutOfStock)) {
            $arrMessage['parameters'] = ['out_of_stock'];
        }

        return $arrMessage;
    }

}