<?php

namespace Riki\Backend\Plugin;

use Magento\Backend\App\AbstractAction;

class AllowedBackendAdminAuthorization
{
    /**
     * @var string
     */
    private $resource = 'Riki_Backend::allowed_magento_backend_admin';

    /**
     * Check role Magento_Backend::admin
     *
     * @param \Magento\Framework\Authorization $subject
     * @param \Closure $proceed
     * @param $resource
     * @param null $privilege
     * @return mixed
     */
    public function aroundIsAllowed(
        \Magento\Framework\Authorization $subject,
        \Closure $proceed,
        $resource,
        $privilege = null
    ) {
        $result = $proceed($resource, $privilege);
        if ($resource == AbstractAction::ADMIN_RESOURCE) {
            if (!$result) {
                $result = $proceed($this->resource, $privilege);
            }
        }
        return $result;
    }
}
