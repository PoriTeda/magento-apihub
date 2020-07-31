<?php
namespace Riki\Rma\Plugin\Sales\Model\Order\Creditmemo;

use Riki\Rma\Helper\Constant;

class Config
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * Config constructor.
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Framework\Registry $registry
    )
    {
        $this->_registry = $registry;
    }

    /**
     * Extend getTotalModels()
     *
     * @param \Magento\Sales\Model\Order\Creditmemo\Config $subject
     * @param $result
     * @return mixed
     */
    public function afterGetTotalModels(\Magento\Sales\Model\Order\Creditmemo\Config $subject, $result)
    {
        if ($this->_registry->registry(Constant::REGISTRY_KEY_DISABLE_COLLECT_TOTAL_CREDIT_MEMO)) {
            $ignore = [
                'fee', 'shipping'
            ];
            foreach ($result as $k => $r) {
                if (in_array($k, $ignore)) {
                    unset($result[$k]);
                }
            }
        }

        return $result;
    }
}