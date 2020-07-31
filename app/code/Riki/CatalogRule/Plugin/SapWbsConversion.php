<?php

namespace Riki\CatalogRule\Plugin;

class SapWbsConversion
{
    /**
     * @var \Riki\CatalogRule\Helper\WbsConversionHelper
     */
    protected $wbsConversionHelper;

    protected $wbsConversion = [];

    /**
     * SapWbsConversion constructor.
     *
     * @param \Riki\CatalogRule\Helper\WbsConversionHelper $wbsConversionHelper
     */
    public function __construct(
        \Riki\CatalogRule\Helper\WbsConversionHelper $wbsConversionHelper
    ) {
        $this->wbsConversionHelper = $wbsConversionHelper;
    }

    /**
     * convert WBS for SAP Exported
     *
     * @param \Riki\SapIntegration\Helper\Data $subject
     * @param $result
     * @return string
     */
    public function afterConvertWbsForSapExported(
        \Riki\SapIntegration\Helper\Data $subject,
        $result
    ) {
        if (!empty($result)) {
            $result = $this->getWbsForSapExported($result);
        }
        return $result;
    }

    /**
     * get Wbs for SAP exported
     *
     * @param $wbs
     * @return string
     */
    public function getWbsForSapExported($wbs)
    {
        if (!isset($this->wbsConversion[$wbs])) {
            $this->wbsConversion[$wbs] = $this->wbsConversionHelper->convertWbsForSapExported($wbs);
        }

        return $this->wbsConversion[$wbs];
    }
}
