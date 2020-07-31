<?php
namespace Riki\Customer\Plugin\Address;

class BeforeValidateAddress {
    public function afterIsRequired($subject,$result){
        if($subject->getAttributeCode() == 'city'){
            return false;
        }
        return $result;
    }
}