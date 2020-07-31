<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\SubscriptionFrequency\Api\Data;

/**
 * Frequency page interface.
 * @api
 */
interface FrequencyInterface
{
    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const FREQUENCY_ID            = 'frequency_id';
    const COURSE_ID               = 'course_id';
    const FREQUENCY_UNIT          = 'frequency_unit';
    const FREQUENCY_INTERVAL      = 'frequency_interval';

    /**#@-*/

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId();

    /**
     * Get identifier
     *
     * @return string
     */
    public function getCourseId();

    /**
     * Get title
     *
     * @return string|null
     */
    public function getFrequencyUnit();

    /**
     * Get page layout
     *
     * @return string|null
     */
    public function getFrequencyInterval();

    /**
     * Set ID
     *
     * @param int $id
     * @return \Riki\SubscriptionFrequency\Api\Data\FrequencyInterface
     */
    public function setId($id);

    /**
     * Set identifier
     *
     * @param string $identifier
     * @return \Riki\SubscriptionFrequency\Api\Data\FrequencyInterface
     */
    public function setCourseId($courseId);

    /**
     * Set title
     *
     * @param string $title
     * @return \Riki\SubscriptionFrequency\Api\Data\FrequencyInterface
     */
    public function setFrequencyUnit($unit);

    /**
     * Set page layout
     *
     * @param string $pageLayout
     * @return \Riki\SubscriptionFrequency\Api\Data\FrequencyInterface
     */
    public function setFrequencyInterval($interval);
}
