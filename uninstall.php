<?php

/**
 * QuizMosaic
 *
 * @author            NimZero <contact@nimzero.fr>
 * @copyright         2023 NimZero
 * @license           GPL v3 or later
 */

// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) die;

/** @var wpdb $wpdb */
global $wpdb;

$wpdb->query(sprintf('DROP TABLES `%snz_quizmosaic_survey`, `%snz_quizmosaic_question`, `%snz_quizmosaic_category`, `%snz_quizmosaic_answer`;', $wpdb->prefix, $wpdb->prefix, $wpdb->prefix, $wpdb->prefix));

delete_option("nz_quizmosaic_db_version");
