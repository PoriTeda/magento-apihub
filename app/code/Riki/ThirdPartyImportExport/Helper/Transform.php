<?php
namespace Riki\ThirdPartyImportExport\Helper;

class Transform
{
    const KEY_METHOD = 'transform';

    protected $_subject;

    /**
     * Set subject
     *
     * @param $subject
     * @return $this
     */
    public function setSubject($subject)
    {
        $this->_subject = $subject;

        return $this;
    }

    /**
     * Get subject
     *
     * @return mixed
     */
    public function getSubject()
    {
        return $this->_subject;
    }

    public function execute($subject = null)
    {
        if ($subject) {
            $this->setSubject($subject);
        }

        $result = [];
        $methods = get_class_methods($this);
        natsort($methods);
        foreach ($methods as $method) {
            if (substr($method, 0, strlen(self::KEY_METHOD)) !== self::KEY_METHOD) {
                continue;
            }

            $key = str_replace(self::KEY_METHOD, '', $method);
            if (!$key) {
                continue;
            }

            if (is_integer($key)) {
                $key = intval($key);
            }

            $result[$key] = $this->$method();
        }

        return $result;
    }
}