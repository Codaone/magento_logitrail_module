<?php
$installer = $this;

$installer->startSetup();

$username = Mage::helper('core')->getRandomString(8);
$password = Mage::helper('core')->getRandomString(12);

$this->setConfigData('carriers/logitrail/webhook_username', $username);
$this->setConfigData('carriers/logitrail/webhook_password', $password);

$installer->run("
	CREATE TABLE IF NOT EXISTS `{$this->getTable('logitrail_log')}` (
		`id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
		`event_id` varchar(255) NOT NULL,
		`event_type` varchar(255) NOT NULL,
		`webhook_id` varchar(255) NOT NULL,
		`timestamp` int NOT NULL,
		`retry_count` int NOT NULL,
		`payload` text NOT NULL
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup();
