<?php
namespace Riki\ThirdPartyImportExport\Helper;

class Validator extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Check a date is a valid date
     * @param $date
     * @param string $format
     * @return bool
     */
    public function isDate($date, $format = 'Y-m-d H:i:s')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }
}