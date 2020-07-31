<?php

namespace Riki\Customer\Api\Data;

/**
 * Customer interface.
 */
interface CustomerInterface extends \Magento\Customer\Api\Data\CustomerInterface
{
    /**#@+
     * Constants defined for keys of the data array. Identical to the name of the getter in snake case
     */

    const IS_ACTIVE = 'is_active';
    const FLAG_EXPORT_BI = 'flag_export_bi';
    const FLAG_SSO_LOGIN = 'flag_sso_login';
    /**#@-*/

    /**
     * Set is active
     *
     * @param $isActive
     * @return mixed
     */
    public function setIsActive($isActive);

    /**
     * Get is active
     *
     * @return mixed
     */
    public function getIsActive();

    /**
     * Set flag export bi
     *
     * @param $flagExportBi
     * @return mixed
     */
    public function setFlagExportBi($flagExportBi);

    /**
     * Get flag export bi
     *
     * @return mixed
     */
    public function getFlagExportBi();

    /**
     * @param $flagSsoLoginAction
     * @return $this
     */
    public function setFlagSsoLoginAction($flagSsoLoginAction);

    /**
     * @return mixed|null
     */
    public function getFlagSsoLoginAction();
}
