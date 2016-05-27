<?php

class Codaone_Logitrail_Block_Logitrail extends Mage_Checkout_Block_Onepage {
    public function __construct() {
        $this->setTemplate('logitrail/container.phtml');      
    }

    public function getForm() {
        return Mage::getModel('logitrail/carrier_logitrail')->getForm();
    }

    public function isTestMode() {
        return Mage::getModel('logitrail/carrier_logitrail')->isTestMode();
    }
}

