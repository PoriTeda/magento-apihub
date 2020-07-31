<?php

namespace Riki\Cookie\Plugin;

use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Theme\Controller\Result\MessagePlugin;

class FilterDataSetCookie
{
    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    /**
     * SetCookieAsSession constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\State $appState
     */
    public function __construct(
        \Magento\Framework\Escaper $escaper
    ) {
        $this->escaper = $escaper;
    }

    /**
     * @param $subject
     * @param $name
     * @param $value
     * @param PublicCookieMetadata $metadata
     * @return array
     */
    public function beforeSetPublicCookie($subject, $name, $value, PublicCookieMetadata $metadata)
    {
        /**
         * NED-112
         * Critical - Cross-Site Scripting: Reflected.
         * Filter data cookie
         */
        if ($name == MessagePlugin::MESSAGES_COOKIES_NAME) {
            $value = $this->filterDataMageMessages($value);
        }
        return [$name, $value, $metadata];
    }

    /**
     * Validate data for cookie mage-messages
     *
     * @param $value
     * @return array|string
     */
    private function filterDataMageMessages($value)
    {
        if (!empty($value)) {
            $messages = json_decode($value, true);
            if (is_array($messages)) {
                $data = [];
                foreach ($messages as $key => $item) {
                    $result = $this->checkKeyDataMessage($item);
                    if (!empty($result)) {
                        $data[$key] = $result;
                    }
                }
                $value = json_encode($data);
            } else {
                $value = $this->escaper->escapeHtml($value);
            }
        }
        return $value;
    }

    /**
     *  Data item of mage message only has [type,text]
     *
     * @param $item
     * @return array|null
     */
    private function checkKeyDataMessage($item)
    {
        if (count($item) == 2 && isset($item['type']) && isset($item['text'])) {
            return [
                'type' => $this->escaper->escapeHtml($item['type']),
                'text' => $this->escaper->escapeHtml($item['text']),
            ];
        }
        return null;
    }
}
