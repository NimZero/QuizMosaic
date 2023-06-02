<?php

/**
 * QuizMosaic
 *
 * @author            NimZero <contact@nimzero.fr>
 * @copyright         2023 NimZero
 * @license           GPL v3 or later
 *
 * @wordpress-plugin
 * Plugin Name:       QuizMosaic
 * Plugin URI:        https://github.com/NimZero/QuizMosaic
 * Description:       Description of the plugin.
 * Version:           1.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            NimZero
 * Author URI:        https://nimzero.fr
 * Text Domain:       nz-plugin
 * License:           GPL v3 or later
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Nimzero\QuizMosaic;

use wpdb;

require_once('src/RestAPIController.php');

class NZQuizMosaic
{
    public function __construct()
    {
        add_action('init', [$this, 'register_assets']);
        add_action('init', [$this, 'shortcodes_init']);
        add_action('rest_api_init', [$this, 'register_api']);
        add_action('admin_menu', [$this, 'options_page']);
    }

    public function activate(): void
    {
        if (get_option('nz_quizmosaic_db_version', null) === null) {
            /** @var wpdb $wpdb */
            global $wpdb;
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            $charset_collate = $wpdb->get_charset_collate();

            dbDelta(sprintf('CREATE TABLE `%snz_quizmosaic_survey` (`id` int(11) NOT NULL AUTO_INCREMENT,`name` varchar(100) NOT NULL,PRIMARY KEY (`id`)) %s;', $wpdb->prefix, $charset_collate));
            dbDelta(sprintf('CREATE TABLE `%snz_quizmosaic_question` (`id` int(11) NOT NULL,`survey_id` int(11) NOT NULL,`question` varchar(255) NOT NULL,PRIMARY KEY (`survey_id`,`id`),KEY `IDX_B6F7494EB3FE509D` (`survey_id`),KEY `IDX_DADD4A25B3FE509D` (`id`)) %s;', $wpdb->prefix, $charset_collate));
            dbDelta(sprintf('CREATE TABLE `%snz_quizmosaic_category` (`id` int(11) NOT NULL,`survey_id` int(11) NOT NULL,`text` varchar(100) NOT NULL,PRIMARY KEY (`survey_id`,`id`),KEY `IDX_64C19C1B3FE509D` (`survey_id`),KEY `IDX_DADD4A2512469DE2` (`id`)) %s;', $wpdb->prefix, $charset_collate));
            dbDelta(sprintf('CREATE TABLE `%snz_quizmosaic_answer` (`survey_id` int(11) NOT NULL,`question_id` int(11) NOT NULL,`category_id` int(11) NOT NULL,`text` varchar(100) NOT NULL,PRIMARY KEY (`survey_id`,`question_id`,`category_id`),KEY `IDX_75EA56E0FB7336F0` (`question_id`),KEY `IDX_75EA56E0E3BD61CE` (`category_id`)) %s;', $wpdb->prefix, $charset_collate));
            dbDelta(sprintf('ALTER TABLE `%snz_quizmosaic_question` ADD CONSTRAINT `FK_B6F7494EB3FE509D` FOREIGN KEY (`survey_id`) REFERENCES `nz_quizmosaic_survey` (`id`);', $wpdb->prefix));
            dbDelta(sprintf('ALTER TABLE `%snz_quizmosaic_category` ADD CONSTRAINT `FK_64C19C1B3FE509D` FOREIGN KEY (`survey_id`) REFERENCES `nz_quizmosaic_survey` (`id`);', $wpdb->prefix));
            dbDelta(sprintf('ALTER TABLE `%snz_quizmosaic_answer` ADD CONSTRAINT `FK_DADD4A2512469DE2` FOREIGN KEY (`survey_id`) REFERENCES `%snz_quizmosaic_survey` (`id`), ADD CONSTRAINT `FK_DADD4A251E27F6BF` FOREIGN KEY (`question_id`) REFERENCES `%snz_quizmosaic_question` (`id`),ADD CONSTRAINT `FK_DADD4A25B3FE509D` FOREIGN KEY (`category_id`) REFERENCES `%snz_quizmosaic_category` (`id`);', $wpdb->prefix, $wpdb->prefix, $wpdb->prefix, $wpdb->prefix));

            add_option("nz_quizmosaic_db_version", "1.0");
        }
    }

    public function deactivate(): void
    {
    }

    public function register_api(): void
    {
        $controller = new RestAPIController();
        $controller->register_routes();
    }

    /**
     * Assets registration
     */
    public function register_assets(): void
    {
        // Plugin assets
        wp_register_script('nz_quizmosaic-plugin', plugins_url('/public/js/plugin.js', __FILE__), deps: ['wp-api', 'backbone'], ver: false, in_footer: true);
        wp_register_style('nz_quizmosaic-plugin', plugins_url('/public/css/plugin.css', __FILE__), deps: [], ver: false, media: 'all');

        // Admin assets
        wp_register_script('nz_quizmosaic-admin', plugins_url('/public/js/admin.js', __FILE__), deps: ['wp-api', 'backbone'], ver: false, in_footer: true);
        wp_register_style('nz_quizmosaic-admin', plugins_url('/public/css/admin.css', __FILE__), deps: [], ver: false, media: 'all');
    }

    /**
     * Queue Scripts and Styles
     */
    function enqueue_assets()
    {
        wp_enqueue_style('nz_quizmosaic-plugin');
        wp_enqueue_script('nz_quizmosaic-plugin');
    }

    /**
     * The [quizmosaic] shortcode
     */
    function short_quizmosaic($atts = [], $content = null, $tag = ''): string
    {
        // normalize attribute keys, lowercase
        $atts = array_change_key_case((array) $atts, CASE_LOWER);

        // override default attributes with user attributes
        $nz_short_attrs = shortcode_atts(
            [
                'questionnaire' => null,
            ],
            $atts,
            $tag
        );

        $content = sprintf('<div id="questionModule" class="nz-quizmosaic--questionModule" data-questionnaire="%s"><div id="app"></div></div>', $nz_short_attrs['questionnaire']);

        $this->enqueue_assets();

        return $content;
    }

    /**
     * Central location to create all shortcodes.
     */
    function shortcodes_init(): void
    {
        add_shortcode('quizmosaic', [$this, 'short_quizmosaic']);
    }

    /**
     * Admin Pages
     */

    public function admin_addmenu_html(): void
    {
        require plugin_dir_path(__FILE__) . 'src/admin/addView.php';
    }

    public function admin_managemenu_html(): void
    {
        require plugin_dir_path(__FILE__) . 'src/admin/manageView.php';
    }

    public function queue_admin_assets(): void
    {
        wp_enqueue_script('nz_quizmosaic-admin');
        wp_enqueue_style('nz_quizmosaic-admin');
    }

    public function options_page(): void
    {
        add_menu_page(
            'Tous les Quiz',
            'QuizMosaic',
            'manage_options',
            'nz_quizmosaic',
            [$this, 'admin_managemenu_html'],
            'dashicons-forms',
            20
        );

        $hook1 = add_submenu_page(
            'nz_quizmosaic',
            'Tous les Quiz',
            'Tous les Quiz',
            'manage_options',
            'nz_quizmosaic',
            [$this, 'admin_managemenu_html'],
        );

        // add_action('load-' . $hook1, [$this, 'queue_admin_assets']);

        $hook2 = add_submenu_page(
            'nz_quizmosaic',
            'Nouveaux Quiz',
            'Ajouter',
            'manage_options',
            'nz_quizmosaic_add',
            [$this, 'admin_addmenu_html'],
        );

        add_action('load-' . $hook2, [$this, 'queue_admin_assets']);

        $hook3 = add_submenu_page(
            'admin.php',
            'Modify Quiz',
            'Modifier',
            'manage_options',
            'nz_quizmosaic_modify',
            [$this, 'admin_addmenu_html'],
        );

        add_action('load-' . $hook3, [$this, 'queue_admin_assets']);
    }
}

if (class_exists('Nimzero\QuizMosaic\NZQuizMosaic')) {
    $nzQuizMosaic = new NZQuizMosaic();
}

/**
 * Activation
 */
register_activation_hook(__FILE__, [$nzQuizMosaic, 'activate']);

/**
 * Deactivation
 */
register_deactivation_hook(__FILE__, [$nzQuizMosaic, 'deactivate']);
