<?php

class Codaone_Logitrail_Block_Logitrail extends Mage_Checkout_Block_Onepage {
	public function _construct() {
		$this->setTemplate('logitrail/container.phtml');
	}

	public function getForm() {
		$locale = explode('_', Mage::app()->getLocale()->getLocaleCode())[0];
		return Mage::getModel('logitrail/carrier_logitrail')->getForm($locale);
	}

	public function isTestMode() {
		return Mage::getModel('logitrail/carrier_logitrail')->isTestMode();
	}
}

