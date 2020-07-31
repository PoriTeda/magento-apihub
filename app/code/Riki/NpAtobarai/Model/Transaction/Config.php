<?php
namespace Riki\NpAtobarai\Model\Transaction;

use Magento\Framework\DataObjectFactory;

class Config
{
    const XML_PATH_NP_ATOBARAI_PENDING_REASON = 'npatobarai/transaction/pending_reason';

    /**
     * @var bool|array
     */
    protected $pendingReasons = false;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serialize;

    /**
     * Config constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Serialize\Serializer\Json $serialize
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Serialize\Serializer\Json $serialize
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->serialize = $serialize;
    }

    /**
     * @param string $code
     * @return string
     */
    public function getPendingReasonMessage($code):string
    {
        $pendingReasons = $this->getPendingReasons();
        if (isset($pendingReasons[$code])) {
            return $pendingReasons[$code];
        }
        return '';
    }

    /**
     * @return array
     */
    public function getPendingReasons(): array
    {
        if ($this->pendingReasons != false) {
            return $this->pendingReasons;
        }

        $reasonList = [];
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $optionsConfig = $this->scopeConfig->getValue(self::XML_PATH_NP_ATOBARAI_PENDING_REASON, $storeScope);
        if ($optionsConfig) {
            try {
                $options = $this->serialize->unserialize($optionsConfig);
            } catch (\Exception $e) {
                $options = false;
            }

            if (is_array($options)) {
                foreach ($options as $option) {
                    if (isset($option['code']) && isset($option['title'])) {
                        $reasonList[$option['code']] = $option['title'];
                    }
                }
            }
        }
        $this->pendingReasons = $reasonList;
        return $this->pendingReasons;
    }
}
