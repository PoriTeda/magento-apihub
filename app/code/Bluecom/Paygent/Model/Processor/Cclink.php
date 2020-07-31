<?php

namespace Bluecom\Paygent\Model\Processor;

use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\entity\ResponseDataFactory;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\system\PaygentB2BModule;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\exception\PaygentB2BModuleConnectException;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\exception\PaygentB2BModuleException;

class Cclink extends \Bluecom\Paygent\Model\Processor\Common
{
    /**
     * Get Result
     *
     * @return array
     */
    public function getResult()
    {
        $result = $this->paymentObject->getResultStatus();
        $responseCode   = $this->paymentObject->getResponseCode();
        $responseDetail = $this->paymentObject->getResponseDetail();
        return $this->_processResult($result, $responseCode, $responseDetail);
    }

    /**
     * Precess Result
     *
     * @param integer $result         Result
     * @param string  $responseCode   ResponseCode
     * @param string  $responseDetail ResponseDetail
     *
     * @return array
     */
    private function _processResult($result, $responseCode, $responseDetail)
    {
        $_data = array();
        $_data["code"]   = $responseCode;
        $_data["detail"] = $responseDetail;

        switch ($result) {
        case 1:
            break;
        case 7:
        case 0:
            $_data = array_merge($_data, $this->_makeResultArray());
            break;
        }

        return $_data;
    }

    /**
     * Make Result to Array.
     *
     * @return array
     */
    private function _makeResultArray()
    {
        $resArray = array();
        $resKey   = array(
            "payment_id",
            "trading_id",
            "issur_class",
            "acq_id",
            "acq_name",
            "issur_name",
            "fc_auth_menu",
            "daiko_code",
            "card_shu_code",
            "k_card_name",
            "shonin_no",
            "out_acs_html"
        );

        if ($this->paymentObject->hasResNext()) {
            $array = $this->paymentObject->resNext();

            foreach ($resKey as $key) {
                if (array_key_exists($key, $array)) {
                    $resArray[$key] = $array[$key];
                }
            }
        }
        return $resArray;
    }

    /**
     * Init Paygent B2B Module
     * 
     * @return $this
     */
    public function init()
    {
        $this->paymentObject = new PaygentB2BModule();
        $this->paymentObject->init();
        $this->setParam("merchant_id", $this->scopeConfig->getValue('payment/paygent/merchant_id'));
        $this->setParam("connect_id", $this->scopeConfig->getValue('payment/paygent/connect_id'));
        $this->setParam("connect_password", $this->_encryptor->decrypt($this->scopeConfig->getValue('payment/paygent/connect_password')));
        $this->setParam("telegram_version", $this->scopeConfig->getValue('payment/paygent/telegram_version'));

        return $this;
    }
}
