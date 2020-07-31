<?php

namespace Riki\ShippingCarrier\Helper;

class CarrierHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CONFIG_SECTION = 'carriers';
    const CONFIG_FIELD_TITLE = 'title';
    const CONFIG_FIELD_COMPANY_CODE = 'company_code';
    const CONFIG_FIELD_COMPANY_METHOD = 'name';
    const CONFIG_FIELD_COMPANY_INQUIRY = 'inquiry_name';
    const CONFIG_FIELD_COMPANY_TRACKING_URL = 'production_webservices_url';
    const CONFIG_FIELD_COD_AGENCY_NAME = 'cod_agency_name';

    /**
     * Get carrier options
     *
     * @return array
     */
    public function getCarrierOptions()
    {
        return [
            \Riki\ShippingCarrier\Model\Carrier\YamatoAskul::CARRIER_CODE => $this->getTitleByCarrierCode(\Riki\ShippingCarrier\Model\Carrier\YamatoAskul::CARRIER_CODE),
            \Riki\ShippingCarrier\Model\Carrier\YamatoGlobal::CARRIER_CODE => $this->getTitleByCarrierCode(\Riki\ShippingCarrier\Model\Carrier\YamatoGlobal::CARRIER_CODE),
            \Riki\ShippingCarrier\Model\Carrier\Bizex::CARRIER_CODE => $this->getTitleByCarrierCode(\Riki\ShippingCarrier\Model\Carrier\Bizex::CARRIER_CODE),
            \Riki\ShippingCarrier\Model\Carrier\Kinki::CARRIER_CODE => $this->getTitleByCarrierCode(\Riki\ShippingCarrier\Model\Carrier\Kinki::CARRIER_CODE),
            \Riki\ShippingCarrier\Model\Carrier\Tokai::CARRIER_CODE => $this->getTitleByCarrierCode(\Riki\ShippingCarrier\Model\Carrier\Tokai::CARRIER_CODE),
            \Riki\ShippingCarrier\Model\Carrier\Yupack::CARRIER_CODE => $this->getTitleByCarrierCode(\Riki\ShippingCarrier\Model\Carrier\Yupack::CARRIER_CODE),
            \Riki\ShippingCarrier\Model\Carrier\Anshin::CARRIER_CODE => $this->getTitleByCarrierCode(\Riki\ShippingCarrier\Model\Carrier\Anshin::CARRIER_CODE),
            \Riki\ShippingCarrier\Model\Carrier\Ecohai::CARRIER_CODE => $this->getTitleByCarrierCode(\Riki\ShippingCarrier\Model\Carrier\Ecohai::CARRIER_CODE),
            \Riki\ShippingCarrier\Model\Carrier\Dummy::CARRIER_CODE => $this->getTitleByCarrierCode(\Riki\ShippingCarrier\Model\Carrier\Dummy::CARRIER_CODE),
            \Riki\ShippingCarrier\Model\Carrier\DummySecond::CARRIER_CODE => $this->getTitleByCarrierCode(\Riki\ShippingCarrier\Model\Carrier\DummySecond::CARRIER_CODE),
            \Riki\ShippingCarrier\Model\Carrier\DummyThird::CARRIER_CODE => $this->getTitleByCarrierCode(\Riki\ShippingCarrier\Model\Carrier\DummyThird::CARRIER_CODE),
            \Riki\ShippingCarrier\Model\Carrier\Sagawa::CARRIER_CODE => $this->getTitleByCarrierCode(\Riki\ShippingCarrier\Model\Carrier\Sagawa::CARRIER_CODE)
        ];
    }

    /**
     * Get carrier code list
     *
     * @return array
     */
    public function getCarrierCodeList()
    {
        return [
            \Riki\ShippingCarrier\Model\Carrier\YamatoAskul::CARRIER_CODE,
            \Riki\ShippingCarrier\Model\Carrier\YamatoGlobal::CARRIER_CODE,
            \Riki\ShippingCarrier\Model\Carrier\Bizex::CARRIER_CODE,
            \Riki\ShippingCarrier\Model\Carrier\Kinki::CARRIER_CODE,
            \Riki\ShippingCarrier\Model\Carrier\Tokai::CARRIER_CODE,
            \Riki\ShippingCarrier\Model\Carrier\Yupack::CARRIER_CODE,
            \Riki\ShippingCarrier\Model\Carrier\Anshin::CARRIER_CODE,
            \Riki\ShippingCarrier\Model\Carrier\Ecohai::CARRIER_CODE,
            \Riki\ShippingCarrier\Model\Carrier\Dummy::CARRIER_CODE,
            \Riki\ShippingCarrier\Model\Carrier\DummySecond::CARRIER_CODE,
            \Riki\ShippingCarrier\Model\Carrier\DummyThird::CARRIER_CODE,
            \Riki\ShippingCarrier\Model\Carrier\Sagawa::CARRIER_CODE
        ];
    }

    /**
     * Get carrier title by carrier code
     *
     * @param $carrierCode
     * @return mixed
     */
    public function getTitleByCarrierCode($carrierCode)
    {
        if (!empty($carrierCode)) {
            $configPath = self::CONFIG_SECTION . '/' . $carrierCode . '/' . self::CONFIG_FIELD_TITLE;
            return $this->getConfigDatabyPath($configPath);
        }

        return '';
    }

    /**
     * Get carrier company code by carrier code
     *
     * @param $carrierCode
     * @return mixed
     */
    public function getCompanyCodeByCarrierCode($carrierCode)
    {
        if (!empty($carrierCode)) {
            $configPath = self::CONFIG_SECTION . '/' . $carrierCode . '/' . self::CONFIG_FIELD_COMPANY_CODE;
            return $this->getConfigDatabyPath($configPath);
        }

        return '';
    }

    /**
     * Get carrier method name by carrier code
     *
     * @param $carrierCode
     * @return mixed
     */
    public function getMethodNameByCarrierCode($carrierCode)
    {
        if (!empty($carrierCode)) {
            $configPath = self::CONFIG_SECTION . '/' . $carrierCode . '/' . self::CONFIG_FIELD_COMPANY_CODE;
            return $this->getConfigDatabyPath($configPath);
        }

        return '';
    }

    /**
     * Get carrier inquiry system name by carrier code
     *
     * @param $carrierCode
     * @return mixed
     */
    public function getInquiryNameByCarrierCode($carrierCode)
    {
        if (!empty($carrierCode)) {
            $configPath = self::CONFIG_SECTION . '/' . $carrierCode . '/' . self::CONFIG_FIELD_COMPANY_CODE;
            return $this->getConfigDatabyPath($configPath);
        }

        return '';
    }

    /**
     * Get carrier Production webservice Url name by carrier code
     *
     * @param $carrierCode
     * @return mixed
     */
    public function getTrackingUrlByCarrierCode($carrierCode)
    {
        if (!empty($carrierCode)) {
            $configPath = self::CONFIG_SECTION . '/' . $carrierCode . '/' . self::CONFIG_FIELD_COMPANY_CODE;
            return $this->getConfigDatabyPath($configPath);
        }

        return '';
    }

    /**
     * Get cod agency name by carrier code
     *
     * @param $carrierCode
     * @return mixed
     */
    public function getCodAgencyNameByCarrierCode($carrierCode)
    {
        if (!empty($carrierCode)) {
            $configPath = self::CONFIG_SECTION . '/' . $carrierCode . '/' . self::CONFIG_FIELD_COD_AGENCY_NAME;
            return $this->getConfigDatabyPath($configPath);
        }

        return '';
    }

    /**
     * Get carrier code by company code
     *
     * @param $companyCode
     * @return string
     */
    public function getCarrierCodeByCompanyCode($companyCode)
    {
        if (!empty($companyCode)) {
            $carrierCodeList = $this->getCarrierCodeList();

            foreach ($carrierCodeList as $carrierCode) {
                if ($this->getCompanyCodeByCarrierCode($carrierCode) == $companyCode) {
                    return $carrierCode;
                }
            }
        }

        return '';
    }

    /**
     * Get config data by path
     *
     * @param $configPath
     * @return mixed
     */
    public function getConfigDatabyPath($configPath)
    {
        return $this->scopeConfig->getValue(
            $configPath,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
    }
}
