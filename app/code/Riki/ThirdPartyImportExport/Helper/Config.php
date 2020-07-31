<?php
namespace Riki\ThirdPartyImportExport\Helper;

class Config extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_SECTION = '';
    const BI_EXPORT_ENABLE = 'bistransaction_data_export_setup/secommon/di_data_export_enable';

    /**
     * Setter/Getter slash transformation cache
     *
     * @var array
     */
    protected static $_slashCache = [];

    /**
     * Set/Get attribute wrapper
     *
     * @param   string $method
     * @param   array $args
     * @return  mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __call($method, $args)
    {
        switch (substr($method, 0, 3)) {
            case 'get':
                $key = $this->_slash(substr($method, 3));
                return $this->getData($key);

        }
        throw new \Magento\Framework\Exception\LocalizedException(
            new \Magento\Framework\Phrase('Invalid method %1::%2', [get_class($this), $method])
        );
    }


    /**
     * Converts field names for setters and getters
     * Uses cache to eliminate unnecessary preg_replace
     *
     * @param string $name
     * @return string
     */
    protected function _slash($name)
    {
        if (isset(self::$_slashCache[$name])) {
            return self::$_slashCache[$name];
        }
        $result = strtolower(trim(preg_replace('/([A-Z]|[0-9]+)/', "/$1", $name), '/'));
        self::$_slashCache[$name] = $result;
        return $result;
    }

    /**
     * Get config data by key
     *
     * @param $key
     * @return mixed
     */
    public function getData($key)
    {
        return $this->scopeConfig->getValue(static::XML_PATH_SECTION . '/' . $key);
    }
}
