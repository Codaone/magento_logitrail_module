<?php

class Codaone_Logitrail_Model_Carrier_Logitrail
	extends Mage_Shipping_Model_Carrier_Abstract
	implements Mage_Shipping_Model_Carrier_Interface {
	protected $_code = 'logitrail';

	/**
	 * Get form block name
	 *
	 * @return string
	 */
	public function getFormBlock() {
		return 'logitrail/logitrail';
	}

	/**
	 * Get form for the checkout block
	 *
	 * @return string
	 */
	public function getForm() {
		$quote = Mage::getSingleton('checkout/session')->getQuote();
		/** @var Mage_Sales_Model_Quote_Item[] $items */
		$items = $quote->getAllItems();
		/** @var \Logitrail\Lib\ApiClient $api */
		$api = Mage::getModel('logitrail/logitrail')->getApi();
		$api->setOrderId($quote->getId());
		foreach ($items as $item) {
			if ($item->getHasChildren()) {
				continue;
			}
			/** @var Mage_Catalog_Model_Product $product */
			$product = Mage::getModel('catalog/product')
				->load($item->getProductId());
			$api->addProduct(
				$product->getId(),
				$item->getName(),
				$item->getQty(),
				$product->getWeight() * 1000, // in grams
				$item->getBasePriceInclTax() - $item->getDiscountAmount(),
				$item->getTaxPercent(),
				$product->getBarcode(),
				$product->getWidth(), // width
				$product->getHeight(), // height
				$product->getLength() // length
			);
		}
		$address = $quote->getShippingAddress();
		$email   = $address->getEmail() ?: $quote->getCustomerEmail();
		// firstname, lastname, phone, email, address, postalCode, city
		$api->setCustomerInfo(
			$address->getFirstname(),
			$address->getLastname(),
			$address->getTelephone(),
			$email,
			join(' ', $address->getStreet()),
			$address->getPostcode(),
			$address->getCity(),
			$address->getCompany()
		);
		$locale = explode('_', Mage::app()->getLocale()->getLocaleCode())[0];
		$form = $api->getForm($locale);
		if ($this->isTestMode()) {
			Mage::log("Order form for Logitrail: $form", NULL, 'logitrail.log');
		}
		return $form;
	}

	/**
	 * Update shipping details with logittail order id and shipping fee
	 *
	 * @return string
	 */
	public function shippingDetails($logitrailId, $price) {
		if ($this->isTestMode()) {
			Mage::log("Shipping details: Logitrail Order Id: $logitrailId, Shipping fee: $price", NULL, 'logitrail.log');
		}
		Mage::getSingleton('core/session')->setLogitrailShippingCost($price);
		$quote   = Mage::getSingleton('checkout/session')->getQuote();
		$address = $quote->getShippingAddress();
		$address->setShippingAmount($price);
		$address->setBaseShippingAmount($price);
		$address->save();
		// Find if our shipping has been included.
		$rates = $address->collectShippingRates()
			->getGroupedAllShippingRates();

		foreach ($rates as $carrier) {
			foreach ($carrier as $rate) {
				$rate->setPrice($price);
				$rate->save();
			}
		}
		$address->setCollectShippingRates(TRUE);

		$quote->setData('logitrail_order_id', $logitrailId)->save();
	}

	/**
	 * Collect rates for this shipping method based on information in $request
	 *
	 * @param Mage_Shipping_Model_Rate_Request $request
	 * @return Mage_Shipping_Model_Rate_Result
	 */
	public function collectRates(Mage_Shipping_Model_Rate_Request $request) {
		/** @var Mage_Shipping_Model_Rate_Result $result */
		/** @var Mage_Shipping_Model_Rate_Result_Method $method */
		$result = Mage::getModel('shipping/rate_result');
		$method = Mage::getModel('shipping/rate_result_method');
		$method->setCarrier($this->_code);
		$method->setCarrierTitle($this->getConfigData('title'));
		$method->setMethod($this->_code);
		$method->setMethodTitle($this->getConfigData('name'));
		$method->setPrice(Mage::getSingleton('core/session')
			->getLogitrailShippingCost());
		$method->setCost(Mage::getSingleton('core/session')
			->getLogitrailShippingCost());
		$result->append($method);
		return $result;
	}

	/**
	 * Get allowed shipping methods
	 *
	 * @return array
	 */
	public function getAllowedMethods() {
		return array($this->_code => $this->getConfigData('name'));
	}

	/**
	 * Are we on test mode or not
	 *
	 * @return boolean
	 */
	public function isTestMode() {
		return $this->getConfigData('testmode') == 1;
	}
}
