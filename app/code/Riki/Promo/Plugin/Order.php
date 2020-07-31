<?php
namespace Riki\Promo\Plugin;

use Riki\AdvancedInventory\Cron\ReAssignation;

class Order
{
    protected $_appState;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    public function __construct(
        \Magento\Framework\App\State $state,
        \Magento\Framework\Registry $registry
    ){
        $this->_appState = $state;
        $this->registry = $registry;
    }

    /**
     * @param \Magento\Sales\Model\Order $subject
     * @param array $result
     * @return array
     */
    public function afterGetAllVisibleItems(
        \Magento\Sales\Model\Order $subject,
        array $result
    ) {

        try {
            if($this->_appState->getAreaCode() == \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE) {
                return $result;
            }
        } catch (\Exception $e) {
            return $result;
        }

        $newResult = [];

        foreach($result as $item){

            if($item->getVisibleUserAccount() !== 0){
                $newResult[] = $item;
            }
        }

        return $newResult;
    }

    /**
     * @param \Magento\Sales\Model\Order $subject
     * @param $result
     * @return mixed
     */
    public function afterGetShipmentsCollection(
        \Magento\Sales\Model\Order $subject,
        $result
    ) {

        try {
            if ($this->_appState->getAreaCode() == \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE
                || $this->registry->registry(ReAssignation::IS_REASSIGNATION_CRON_NAME)
            ) {
                return $result;
            }
        } catch (\Exception $e) {
            return $result;
        }

        if($result){
            $result->addFieldToFilter('visible_user_account', 1);
        }

        return $result;
    }
}
