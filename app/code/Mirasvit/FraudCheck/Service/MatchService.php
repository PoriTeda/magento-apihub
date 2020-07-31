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


namespace Mirasvit\FraudCheck\Service;

use GeoIp2\Database\Reader as GeoIp2Reader;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\DataObject;
use Mirasvit\FraudCheck\Api\Service\MatchServiceInterface;
use Mirasvit\FraudCheck\Model\Config;

class MatchService implements MatchServiceInterface
{
    /**
     * @var CacheInterface
     */
    private $cache;

    private $config;

    public function __construct(
        CacheInterface $cache,
        Config $config
    ) {
        $this->cache = $cache;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getIpLocation($ip)
    {
        $reader = new GeoIp2Reader(dirname(dirname(__FILE__)) . '/Setup/GeoLite2-City.mmdb');

        $data = false;
        try {
            $data = $reader->city($ip);
        } catch (\Exception $e) {
        }

        if ($data) {
            return new DataObject([
                'lat' => $data->location->latitude,
                'lng' => $data->location->longitude,
                'country_code' => $data->country->isoCode,
            ]);
        } else {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCoordinates($country, $city, $street, $province)
    {
        if (!$country && !$city && !$street && !$province) {
            return false;
        }

        $address = urlencode($country . ',' . $city . ',' . $street . ',' . $province);
        $url = "https://maps.google.com/maps/api/geocode/json?address=$address&sensor=false&key=" . $this->config->getGoogleApiKey();
        $response = $this->requestUrl($url);

        if ($response->getData('status') == 'ZERO_RESULTS') {
            return false;
        } else {
            return new DataObject([
                'lat' => $response->getData('results/0/geometry/location/lat'),
                'lng' => $response->getData('results/0/geometry/location/lng'),
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFacebookUrl($firstName, $lastName)
    {
        $firstName = explode(' ', $firstName)[0];

        $combinations = [
            "$firstName.$lastName",
            "$lastName.$firstName",
            "$lastName$firstName",
            "$firstName$lastName",
            "$lastName",
        ];

        foreach ($combinations as $nick) {
            $nick = strtolower($nick);
            if ($nick) {
                $url = 'https://www.facebook.com/' . $nick;
                $headers = $this->requestHeaders($url);

                if (strpos($headers, '404') === false) {
                    return $url;
                }
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getTwitterUrl($firstName, $lastName)
    {
        $firstName = explode(' ', $firstName)[0];

        $combinations = [
            "$firstName.$lastName",
            "$lastName.$firstName",
            "$lastName$firstName",
            "$firstName$lastName",
            "$lastName",
        ];

        foreach ($combinations as $nick) {
            $nick = strtolower($nick);
            if ($nick) {
                $url = 'https://www.twitter.com/' . $nick;
                $headers = $this->requestHeaders($url);

                if (strpos($headers, '404') === false) {
                    return $url;
                }
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getLinkedInUrl($firstName, $lastName)
    {
        $firstName = explode(' ', $firstName)[0];

        $combinations = [
            "$firstName.$lastName",
            "$lastName.$firstName",
            "$lastName$firstName",
            "$firstName$lastName",
            "$lastName",
        ];

        foreach ($combinations as $nick) {
            $nick = strtolower($nick);
            if ($nick) {
                $url = 'https://www.linkedin.com/in/' . $nick;
                $headers = $this->requestHeaders($url);

                if (strpos($headers, '404') === false) {
                    return $url;
                }
            }
        }

        return false;
    }

    /**
     * @param string $url
     * @return string
     */
    private function requestHeaders($url)
    {
        if ($this->cache->load($url)) {
            $headers = $this->cache->load($url);
        } else {
            try {
                $headers = get_headers($url);
                $headers = implode(',', $headers);
            } catch (\Exception $e) {
                $headers = '-';
            }
            $this->cache->save($headers, $url);
        }

        return $headers;
    }

    /**
     * @param string $url
     * @return DataObject
     */
    private function requestUrl($url)
    {
        if ($this->cache->load($url)) {
            $response = $this->cache->load($url);
        } else {
            try {
                $response = file_get_contents($url);
                $this->cache->save($response, $url);
            } catch (\Exception $e) {
                $response = \Zend_Json_Encoder::encode([]);
            }
        }

        $response = \Zend_Json_Decoder::decode($response);

        return new DataObject($response);
    }
}