<?php
namespace Riki\Framework\Helper;

use Magento\Framework\App\Area;

class Scope
{
    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * Scope constructor.
     *
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\State $appState
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\State $appState
    ) {
        $this->registry = $registry;
        $this->appState = $appState;
    }

    /**
     * Is area frontend
     *
     * @return bool
     */
    public function isInFrontend()
    {
        return $this->appState->getAreaCode() == Area::AREA_FRONTEND;
    }

    /**
     * Is area adminhtml
     *
     * @return bool
     */
    public function isInAdminhtml()
    {
        return $this->appState->getAreaCode() == Area::AREA_ADMINHTML;
    }

    /**
     * Is area admin
     *
     * @return bool
     */
    public function isInAdmin()
    {
        return $this->appState->getAreaCode() == Area::AREA_ADMIN;
    }

    /**
     * Is area cron
     *
     * @return bool
     */
    public function isInCron()
    {
        return $this->appState->getAreaCode() == Area::AREA_CRONTAB;
    }

    /**
     * Is in a function
     *
     * @param $function
     *
     * @return mixed
     */
    public function isInFunction($function)
    {
        return $this->registry->registry($function);
    }

    /**
     * Go in a function
     *
     * @param $function
     * @param bool $params
     *
     * @return $this
     */
    public function inFunction($function, $params = true)
    {
        $function = trim($function, '\\');
        $this->registry->unregister($function);
        $this->registry->register($function, $params);
        return $this;
    }

    /**
     * Go out a function
     *
     * @param $function
     *
     * @return $this
     */
    public function outFunction($function)
    {
        $this->registry->unregister($function);
        return $this;
    }
}