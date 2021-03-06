<?php

class Codaone_Logitrail_Model_Logitrail extends Mage_Core_Model_Abstract {

	protected $_api = FALSE;

	public function _construct() {
		$libDir = Mage::getBaseDir('lib');
		if (is_file($libDir . '/logitrail/lib/Logitrail/Lib/ApiClient.php')) {
			require_once $libDir . '/logitrail/lib/Logitrail/Lib/ApiClient.php';
		}
		elseif (is_file($libDir . '/Logitrail/Lib/ApiClient.php')) {
			require_once $libDir . '/Logitrail/Lib/ApiClient.php';
		}
		else {
			Mage::throwException('Logitrail library files missing');
			return;
		}
		$api = new \Logitrail\Lib\ApiClient();
		$api->setMerchantId($this->_getConfig('merchantid'));
		$api->setSecretKey($this->_getConfig('secretkey'));
		$api->useTest(Mage::getModel('logitrail/carrier_logitrail')
			->isTestMode());
		$this->_api = $api;
	}

	public function getApi() {
		return $this->_api;
	}

	/*
	* Confirm order delivery to Logitrail
	*
	*/
	public function confirmOrder($order) {
		/** @var $order Mage_Sales_Model_Order */
		if ($order->getShippingMethod() == 'logitrail_logitrail') {
			Mage::getSingleton('core/session')->setLogitrailShippingCost(0);
			$api = $this->getApi();
			$api->setResponseAsRaw(TRUE);
			$logitrailId = $order->getLogitrailOrderId();

			$address = $order->getShippingAddress();
			$email   = $address->getEmail() ?: $order->getCustomerEmail();

			$api->setCustomerInfo(
				$address->getFirstname(),
				$address->getLastname(),
				$address->getTelephone(),
				$email,
				join(' ', $address->getStreet()),
				$address->getPostcode(),
				$address->getCity(),
				$address->getCompany(),
				$address->getCountry()
			);
			$api->setOrderId($order->getIncrementId());
			$api->updateOrder($logitrailId);

			$rawResponse = $api->confirmOrder($logitrailId);
			$response    = json_decode($rawResponse, TRUE);
			if ($response) {

				if ($this->_getConfig('autoship') && $order->canShip()) {
					$qty = array();
					foreach ($order->getAllItems() as $item) {
						$Itemqty             = $item->getQtyOrdered();
						$qty[$item->getId()] = $item->getQtyOrdered();
					}

					$shipment = $order->prepareShipment($qty);
					$shipment->register();
					$shipment->addComment(Mage::helper('logitrail')
						->__("Tracking URL: " . str_replace('\\', '', $response['tracking_url'])));
					$track = Mage::getModel('sales/order_shipment_track')
						->addData(array(
							'carrier_code' => 'custom',
							'title'        => 'Logitrail',
							'number'       => $response['tracking_code']
						));
					$shipment->addTrack($track);
					$shipment->getOrder()->setIsInProcess(TRUE);
					$transactionSave = Mage::getModel('core/resource_transaction')
						->addObject($shipment)
						->addObject($order)
						->save();

					$shipment->sendEmail(TRUE, Mage::helper('logitrail')
						->__("Tracking URL: " . str_replace('\\', '', $response['tracking_url'])));
					$shipment->setEmailSent(TRUE);
					$order->save();
				} // if autoship

				$order->addStatusHistoryComment(sprintf(Mage::helper('logitrail')
					->__("Logitrail Order Id: %s, Tracking number: %s, Tracking URL: %s"), $logitrailId, $response['tracking_code'], str_replace('\\', '', $response['tracking_url'])));

				if (Mage::getModel('logitrail/carrier_logitrail')
					->isTestMode()
				) {
					Mage::log("Confirmed order $logitrailId, response $rawResponse", NULL, 'logitrail.log');
				}
			}
			else {  // confirmation failed
				$order->addStatusHistoryComment(Mage::helper('logitrail')
					->__('Error: could not confirm order to Logitrail. Logitrail Order Id: ' . $logitrailId));
				Mage::log("Error: could not confirm order to Logitrail. Logitrail Order Id:  $logitrailId Response: $rawResponse", Zend_Log::ERR);
				if (Mage::getModel('logitrail/carrier_logitrail')
					->isTestMode()
				) {
					Mage::log("Error: could not confirm order to Logitrail. Logitrail Order Id:  $logitrailId Response: $rawResponse", NULL, 'logitrail.log');
				}
			}
		}
	}

	/*
	*
	* Add product to Logitrail
	* $param array of product id's
	* @return mixed: true on success, string error message on failure.
	*
	*/
	public function addProducts($productIds) {
		$api = $this->getApi();
		$api->setResponseAsRaw(TRUE);
		$store          = Mage::app()->getStore('default');
		$taxCalculation = Mage::getModel('tax/calculation');
		$request        = $taxCalculation->getRateRequest(NULL, NULL, NULL, $store);

		$api->clearAll();
		foreach ($productIds as $productId) {
			$product    = Mage::getModel('catalog/product')->load($productId);
			$taxClassId = $product->getTaxClassId();
			$taxPercent = $taxCalculation->getRate($request->setProductClassId($taxClassId));
			$api->addProduct($product->getId(),
				$product->getName(),
				$product->getStockItem()->getQty(),
				$product->getWeight() * 1000, // in grams
				$product->getPrice(),
				$taxPercent,
				$product->getBarcode(),
				$product->getWidth(), // width
				$product->getHeight(), // height
				$product->getLength() // length
			);
		}
		$results      = $api->createProducts();
		$success      = TRUE;
		$failed       = array();
		$errorMessage = '';
		foreach ($results as $result) {
			$status = json_decode($result, TRUE);
			if ($status === FALSE) {
				// not correct json
				$success      = TRUE;
				$errorMessage = Mage::helper('logitrail')
					->__('Error creating/updating products');
				Mage::log("Error: could not create/update product Logitrail. Response: " . print_r($results, TRUE), Zend_Log::ERR);
				if (Mage::getModel('logitrail/carrier_logitrail')
					->isTestMode()
				) {
					Mage::log("Error: could not create/update product Logitrail. Response: " . print_r($results, TRUE), NULL, 'logitrail.log');
				}
				return $errorMessage;  // can not recover
			}
			if ($status['success'] != 1) {
				$success  = FALSE;
				$failed[] = $status['id'];
			}
		}
		if (!$success) {
			$errorMessage = Mage::helper('logitrail')
					->__('Failed creating/updating product IDs: ') . join(', ', $failed);
			Mage::log("Error: could not create/update product Logitrail. Response: " . join(",", $results), Zend_Log::ERR);
			if (Mage::getModel('logitrail/carrier_logitrail')->isTestMode()) {
				Mage::log("Error: could not create product to Logitrail. Response:  " . print_r($results, TRUE), NULL, 'logitrail.log');
			}
			return $errorMessage;
		}
		if (Mage::getModel('logitrail/carrier_logitrail')->isTestMode()) {
			Mage::log("Created/updated products to Logitrail. Product IDs: " . join(',', $productIds) . " Logitrail response " . print_r($results, TRUE), NULL, 'logitrail.log');
		}
		return TRUE;
	}

	public function _getConfig($name) {
		return Mage::getStoreConfig('carriers/logitrail/' . $name);
	}
}

