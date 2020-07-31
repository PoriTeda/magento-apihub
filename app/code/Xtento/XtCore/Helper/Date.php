<?php

/**
 * Product:       Xtento_XtCore (2.1.0)
 * ID:            %!uniqueid!%
 * Packaged:      %!packaged!%
 * Last Modified: 2017-08-16T08:52:13+00:00
 * File:          app/code/Xtento/XtCore/Helper/Date.php
 * Copyright:     Copyright (c) 2017 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\XtCore\Helper;

class Date extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $localeDate;

    /**
     * Date constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
    ) {
        parent::__construct($context);
        $this->localeDate = $localeDate;
    }

    /*
     * Convert date to local timezone timestamp. This is important so strftime() in the XSL Template returns the correct time zone.
     */
    public function convertDateToStoreTimestamp($date, $store = null)
    {
        try {
            // Temporary DateTime object to get scope timezone
            $tempLocaleDate = $this->localeDate->scopeDate(
                $store,
                $date,
                true
            );

            // Retrieve the correct store timezone - we can use the date above for source.
            $mageTimezone = $tempLocaleDate->getTimezone();

            // Create a temporary DateTime object with the utc date and utc timestamp
            $tempConversionDate = new \DateTime(is_numeric($date) ? '@' . $date : $date, new \DateTimeZone('UTC'));

            // Set the timezone correction to the newly created DateTime to make date conversion
            $tempConversionDate->setTimezone($mageTimezone);

            // Pass through a temporary string representation
            $convertedString = $tempConversionDate->format('Y-m-d H:i:s');

            // Convert string to timestamp since ->format('u') will always return in UTC regardless of set timestamp
            $convertedTimestamp = strtotime($convertedString);

            // Timezone-corrected timestamp
            return $convertedTimestamp;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function convertDateToUtc($date, $store = null) {

    }
}
