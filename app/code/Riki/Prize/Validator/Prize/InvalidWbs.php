<?php
namespace Riki\Prize\Validator\Prize;

class InvalidWbs extends \Riki\Catalog\Validator\Wbs
{
    /**
     * {@inheritdoc}
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function isValid($value)
    {
        if ($value instanceof \Riki\Prize\Model\Prize) {
            $value = $value->getWbs();
        }

        return parent::isValid($value);
    }
}