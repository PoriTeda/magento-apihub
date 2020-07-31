<?php
/**
 * CvsPayment
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\CvsPayment
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\CvsPayment\Helper;

use Riki\CvsPayment\Api\ConstantInterface;

/**
 * Class Data
 *
 * @category  RIKI
 * @package   Riki\CvsPayment\Helper
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Get config from core_config_data
     *
     * @param string $scope scope
     *
     * @return mixed
     */
    public function getCancelDays($scope = 'default')
    {
        return $this->scopeConfig
            ->getValue(ConstantInterface::CONFIG_PATH_CANCEL_DAYS, $scope);
    }

    /**
     * Get config from core_config_data
     *
     * @param string $scope scope
     *
     * @return mixed
     */
    public function getCancelCronSetting($scope = 'default')
    {
        return $this->scopeConfig
            ->getValue(ConstantInterface::CONFIG_PATH_CANCEL_CRON_SETTING, $scope);
    }

    /**
     * Get config from core_config_data
     *
     * @param string $scope scope
     *
     * @return mixed
     */
    public function getCancelEmailNotification($scope = 'default')
    {
        return $this->scopeConfig
            ->getValue(
                ConstantInterface::CONFIG_PATH_CANCEL_EMAIL_NOTIFICATION,
                $scope
            );
    }
}
