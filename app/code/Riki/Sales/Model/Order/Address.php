<?php
namespace Riki\Sales\Model\Order;

class Address extends \Magento\Sales\Model\Order\Address
{
    /**
     * Get full customer name
     *
     * @return string
     */
    public function getName()
    {
        $name = '';
        if ($this->getPrefix()) {
            $name .= $this->getPrefix() . ' ';
        }

        $name .= ' ' . $this->getLastname();
        $name .= ' ' . $this->getLastnamekana();

        if ($this->getMiddlename()) {
            $name .= ' ' . $this->getMiddlename();
        }

        $name .= $this->getFirstname();
        $name .= $this->getFirstnamekana();

        if ($this->getSuffix()) {
            $name .= ' ' . $this->getSuffix();
        }

        return $name;
    }
}