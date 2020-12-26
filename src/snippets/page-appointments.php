<?php

declare(strict_types = 1);

/**
 * Template Name: Appointments
 */

if ( ! defined('ABSPATH')) {
	exit;
}

get_header(); ?>
    <div id="appointments">
        <div class="container">
            <h1 class="appointments-title">All Appointments</h1>
			<?php echo Appointments::getInstance()->getContent(); ?>
        </div>
    </div>
<?php get_footer();