<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-fraud-check
 * @version   1.0.38
 * @copyright Copyright (C) 2019 Mirasvit (https://mirasvit.com/)
 */


namespace Mirasvit\FraudCheck\Api\Service;

interface MatchServiceInterface
{
    /**
     * @param string $ip
     * @return IpLocationInterface|false
     */
    public function getIpLocation($ip);

    /**
     * @param string $firstName
     * @param string $lastName
     * @return string|false
     */
    public function getFacebookUrl($firstName, $lastName);

    /**
     * @param string $firstName
     * @param string $lastName
     * @return string|false
     */
    public function getLinkedInUrl($firstName, $lastName);

    /**
     * @param string $firstName
     * @param string $lastName
     * @return string|false
     */
    public function getTwitterUrl($firstName, $lastName);

    /**
     * @param string $country
     * @param string $city
     * @param string $street
     * @param string $province
     * @return CoordinateInterface|false
     */
    public function getCoordinates($country, $city, $street, $province);
}

interface IpLocationInterface
{
    /**
     * @return float
     */
    public function getLat();

    /**
     * @return float
     */
    public function getLng();

    /**
     * @return string
     */
    public function getCountryCode();
}

interface CoordinateInterface
{
    /**
     * @return float
     */
    public function getLat();

    /**
     * @return float
     */
    public function getLng();
}