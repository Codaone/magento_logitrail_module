<?php
/**
 * Created Time: 17.3.2017
 * @author: Codaone Oy
 * @author: Juhani Haapala
 * @author: juhani@codaone.fi
 */

class Codaone_Logitrail_WebhookController extends Mage_Core_Controller_Front_Action {

	function updateAction(){
		/** @var \Logitrail\Lib\ApiClient $api */
		$apic = Mage::getModel('logitrail/logitrail')->getApi();
		$hash = explode(' ', apache_request_headers()['Authorization'])[1];
		$auth = explode(':', base64_decode($hash));
		$currentUsername = Mage::getStoreConfig('carriers/logitrail/webhook_username');
		$currentPassword = Mage::getStoreConfig('carriers/logitrail/webhook_password');

		if ($auth[0] == $currentUsername && $auth[1] == $currentPassword) {
			$received_data = $apic->processWebhookData(file_get_contents('php://input'));

			/** @var Mage_Core_Model_Resource $resource */
			$resource = Mage::getSingleton('core/resource');
			/** @var Magento_Db_Adapter_Pdo_Mysql $writeConnection */
			$writeConnection = $resource->getConnection('core_write');
			if ($received_data) {
				$writeConnection->insert($resource->getTableName('logitrail_log'), array(
					'event_id'    => $received_data['event_id'],
					'event_type'  => $received_data['event_type'],
					'webhook_id'  => $received_data['webhook_id'],
					'timestamp'   => $received_data['ts'],
					'retry_count' => $received_data['retry_count'],
					'payload'     => json_encode($received_data['payload'])
				));
				switch ($received_data['event_type']) {
					case "product.inventory.change":
						foreach ($received_data['payload'] as $product) {
							/** @var Mage_CatalogInventory_Model_Stock_Item $stockItem */
							$stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product['merchants_id']);
							if ($stockItem->getId() > 0 and $stockItem->getManageStock()) {
								$qty = $product['inventory']['available'];
								$stockItem->setQty($qty);
								$stockItem->setIsInStock((int)($qty > 0));
								$stockItem->save();
							}
						}
						break;
					case "order.shipped":
						$orderId = $received_data['merchants_id'];
						/** @var Mage_Sales_Model_Order $order */
						$order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
						if ($order->canShip()) {
							$qty = array();
							foreach ($order->getAllItems() as $item) {
								/** @var Mage_Sales_Model_Order_Item $item */
								$qty[$item->getId()] = $item->getQtyOrdered();
							}
							$trackingCode = $received_data['order']['tracking_code'];
							$trackingUrl = $received_data['order']['tracking_url'];

							$shipment = $order->prepareShipment($qty);
							$shipment->register();
							$track = Mage::getModel('sales/order_shipment_track')
								->addData(array(
									'carrier_code' => 'custom',
									'title'        => 'Logitrail',
									'number'       => $trackingCode
								));
							$shipment->addTrack($track);
							$shipment->getOrder()->setIsInProcess(TRUE);
							Mage::getModel('core/resource_transaction')
								->addObject($shipment)
								->addObject($order)
								->save();

							$shipment->sendEmail(TRUE, Mage::helper('logitrail')
								->__("Tracking URL: " . str_replace('\\', '', $trackingUrl)));
							$shipment->setEmailSent(TRUE);
							$order->save();
						}
						break;
				}
			}
		}
	}

}