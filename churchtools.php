<?php

declare(strict_types = 1);

/**
 * Plugin Name:       Church Tools
 * Plugin URI:        https://github.com/julian-wendt/wp-churchtools
 * Description:       Use ChurchTools API to save calender events into the database and use them on the website.
 * Version:           1.0.0
 * Author:            Julian Wendt, Johannes Leupold
 */

use ChurchTools\ChurchEvents\Events;

if ( ! defined('ABSPATH')) {
	exit;
}

require __DIR__ . '/core/autoloader.php';

$autoloader = new ChurchToolsAutoloader();
$autoloader->register();

ChurchTools::getInstance();

Events::getInstance();

register_activation_hook(__FILE__, [ChurchTools::getInstance(), 'activate']);
register_deactivation_hook(__FILE__, [ChurchTools::getInstance(), 'deactivate']);