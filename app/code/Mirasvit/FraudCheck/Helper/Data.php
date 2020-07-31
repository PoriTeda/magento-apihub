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
 * @version   1.0.6
 * @copyright Copyright (C) 2016 Mirasvit (https://mirasvit.com/)
 */


namespace Mirasvit\FraudCheck\Helper;

use Magento\Framework\DataObject;
use Magento\Framework\App\CacheInterface;
use Mirasvit\FraudCheck\Model\Score;

class Data
{
    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var GeoIP
     */
    protected $geoIP;

    /**
     * @param CacheInterface $cache
     * @param GeoIP          $geoIP
     */
    public function __construct(
        CacheInterface $cache,
        GeoIP $geoIP
    ) {
        $this->cache = $cache;
        $this->geoIP = $geoIP;
    }

    /**
     * @param string $url
     * @return DataObject
     */
    public function requestUrl($url)
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

    /**
     * @param string $ip
     * @return DataObject
     */
    public function getIpLocation($ip)
    {
        $this->geoIP->open(dirname(__FILE__) . '/GeoLiteCity.dat', 0);

        $data = $this->geoIP->recordByAddr($ip);

        if ($data) {
            return $data;
        } else {
            return new DataObject([]);
        }
    }

    /**
     * @param string $country
     * @param string $city
     * @param string $street
     * @param string $province
     * @return array|bool
     */
    public function getCoordinates($country, $city, $street, $province)
    {
        $address = urlencode($country . ',' . $city . ',' . $street . ',' . $province);
        $url = "http://maps.google.com/maps/api/geocode/json?address=$address&sensor=false";

        $response = $this->requestUrl($url);

        if ($response->getData('status') == 'ZERO_RESULTS') {
            return false;
        } else {
            return [
                'lat' => $response->getData('results/0/geometry/location/lat'),
                'lng' => $response->getData('results/0/geometry/location/lng')
            ];
        }
    }

    /**
     * @param string $status
     * @param float  $score
     * @return string
     */
    public function getScoreBadgeHtml($status, $score)
    {
        if ($status == Score::STATUS_APPROVE) {
            $label = __('Accept');
        } elseif ($status == Score::STATUS_REVIEW) {
            $label = __('Review');
        } else {
            $label = __('Reject');
        }

        return '<span class="fc__score-badge status-' . $status . '">'
         . $score . '<span>' . $label . '</span> <i class="fa"></i></span>';
    }
}