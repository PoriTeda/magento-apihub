<?php
namespace Riki\Framework\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;

class ScopeConfig extends \Magento\Framework\App\Helper\AbstractHelper
{
    const SEPARATE = '_';
    const READ_MODE = 1;
    const WRITE_MODE = 2;

    /**
     * @var array
     */
    protected static $underscoreCache;

    /**
     * @var string
     */
    protected $group;

    /**
     * @var string
     */
    protected $section;

    /**
     * @var string
     */
    protected $field;

    /**
     * @var mixed
     */
    protected $context;

    /**
     * @var bool
     */
    protected $mode;


    /**
     * @var \Magento\Framework\App\Config\ConfigResource\ConfigInterface
     */
    protected $configResource;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopePool;

    /**
     * ScopeConfig constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopePool
     * @param \Magento\Framework\App\Config\ConfigResource\ConfigInterface $configResource
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopePool,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $configResource,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->scopePool = $scopePool;
        $this->configResource = $configResource;

        parent::__construct($context);

        $this->init();
    }

    /**
     * Initialize
     *
     * @return void
     */
    public function init()
    {
        $this->context = null;
        $this->group = null;
        $this->section = null;
        $this->field = null;
        $this->mode = self::READ_MODE;
    }

    /**
     * Converts field names for setters and getters
     *
     * Uses cache to eliminate unnecessary preg_replace
     *
     * @param string $name name
     *
     * @return string
     */
    protected function _underscore($name)
    {
        if (isset(self::$underscoreCache[$name])) {
            return self::$underscoreCache[$name];
        }
        $result = strtolower(
            trim(preg_replace('/([A-Z]|[0-9]+)/', "_$1", $name), '_')
        );
        self::$underscoreCache[$name] = $result;
        return $result;
    }

    /**
     * Read config from context
     *
     * @param $context
     *
     * @return $this
     */
    public function read($context)
    {
        $this->init();
        $this->context = $context;
        $this->mode = self::READ_MODE;

        return $this;
    }

    /**
     * Write config into context
     *
     * @param $context
     *
     * @return $this
     */
    public function write($context)
    {
        $this->init();
        $this->context = $context;
        $this->mode = self::WRITE_MODE;

        return $this;
    }

    /**
     * Is in read mode
     *
     * @return bool
     */
    public function isRead()
    {
        return $this->mode === self::READ_MODE;
    }

    /**
     * Is in write mode
     *
     * @return bool
     */
    public function isWrite()
    {
        return $this->mode === self::WRITE_MODE;
    }


    /**
     * Call magic method
     *
     * @param $name
     * @param $arguments
     *
     * @return $this|mixed|null
     */
    public function __call($name, $arguments)
    {
        $name = strtoupper($this->_underscore($name));

        if (!$this->group) {
            $this->group = $name;
            return $this;
        }

        if (!$this->section) {
            $this->section = $name;
            return $this;
        }

        if (!$this->field) {
            $this->field = $name;
        }

        $const = $this->context . '::' . $this->group . self::SEPARATE
            . $this->section . self::SEPARATE . $this->field;

        if (!defined($const)) {
            return null;
        }

        if ($this->isRead()) {
            $scope = isset($arguments[0])
                ? $arguments[0]
                : ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            $scopeId = isset($arguments[1]) ? $arguments[1] : 0;
            $this->init();
            return $this->scopeConfig->getValue(constant($const), $scope, $scopeId);

        } else if ($this->isWrite()) {
            $scope = isset($arguments[1])
                ? $arguments[1]
                : ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            $scopeId = isset($arguments[2]) ? $arguments[2] : 0;
            $this->init();

            if (isset($arguments[0])) {
                $this->configResource->saveConfig(constant($const), $arguments[0], $scope, $scopeId);
            } else {
                $this->configResource->deleteConfig(constant($const), $scope, $scopeId);
            }
            $this->scopePool->clean();
        }

        return $this;
    }
}