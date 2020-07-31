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
use Mirasvit\FraudCheck\Model\Context;

class Social
{
    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @param CacheInterface $cache
     */
    public function __construct(
        CacheInterface $cache
    ) {
        $this->cache = $cache;
    }

    /**
     * @param Context $context
     * @return bool|string
     */
    public function getFacebookUrl($context)
    {
        $firstname = explode(' ', $context->getFirstname())[0];
        $lastname = $context->getLastname();

        $combinations = [
            "$firstname.$lastname",
            "$lastname.$firstname",
            "$lastname$firstname",
            "$firstname$lastname",
            "$lastname",
        ];

        foreach ($combinations as $nick) {
            $nick = strtolower($nick);
            if ($nick) {
                $url = 'https://www.facebook.com/' . $nick;
                $headers = $this->getHeaders($url);

                if (strpos($headers, '404') === false) {
                    return $url;
                }
            }
        }

        return false;
    }

    /**
     * @param Context $context
     * @return bool|string
     */
    public function getTwitterUrl($context)
    {
        $firstname = explode(' ', $context->getFirstname())[0];
        $lastname = $context->getLastname();

        $combinations = [
            "$firstname.$lastname",
            "$lastname.$firstname",
            "$lastname$firstname",
            "$firstname$lastname",
            "$lastname",
        ];

        foreach ($combinations as $nick) {
            $nick = strtolower($nick);
            if ($nick) {
                $url = 'https://www.twitter.com/' . $nick;
                $headers = $this->getHeaders($url);

                if (strpos($headers, '404') === false) {
                    return $url;
                }
            }
        }

        return false;
    }

    /**
     * @param Context $context
     * @return bool|string
     */
    public function getLinkedInUrl($context)
    {
        $firstname = explode(' ', $context->getFirstname())[0];
        $lastname = $context->getLastname();

        $combinations = [
            "$firstname.$lastname",
            "$lastname.$firstname",
            "$lastname$firstname",
            "$firstname$lastname",
            "$lastname",
        ];

        foreach ($combinations as $nick) {
            $nick = strtolower($nick);
            if ($nick) {
                $url = 'https://www.linkedin.com/in/' . $nick;
                $headers = $this->getHeaders($url);

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
    protected function getHeaders($url)
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
}