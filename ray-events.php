<?php
/*
 * Plugin Name: Ray Events
 * Plugin URI: https://joana.cc
 * Description: Ray Events is a simple plugin used to create and manage events. It includes the shortcode [upcoming_events] which displays 3 upcoming events by default but which can be customised to render more events, usign the parameter [upcoming_events display=6].
 * Version: 1.0
 * Author: Joana Ray
 * Author URI: https://joana.cc/about/
 * Text Domain: ray_events
 * Domain Path: /languages
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
/**
 * Handle ACF
 */
// Bootstrap the Bundled ACF
// Check if another plugin or theme has bundled ACF
if (defined('MY_ACF_PATH')) {
    return;
}
// Define path and URL to the ACF plugin.
define('MY_ACF_PATH', __DIR__ . '/includes/acf/');
define('MY_ACF_URL', plugin_dir_url(__FILE__) . 'includes/acf/');
// Include the ACF plugin.
include_once(MY_ACF_PATH . 'acf.php');
include_once(MY_ACF_PATH . 'acf-fields.php');

// Customize the URL setting to fix incorrect asset URLs.
add_filter('acf/settings/url', 'my_acf_settings_url');
function my_acf_settings_url($url)
{
    return MY_ACF_URL;
}

// Check if the ACF free plugin is activated
if (is_plugin_active('advanced-custom-fields/acf.php')) {
    // Free plugin activated
    // Free plugin activated, show notice
    add_action('admin_notices', function () {
        ?>
        <div class="updated" style="border-left: 4px solid #ffba00;">
            <p>The ACF plugin cannot be activated at the same time as Third-Party Product and has been deactivated. Please keep
                ACF installed to allow you to use ACF functionality.</p>
        </div>
        <?php
    }, 99);

    // Disable ACF free plugin
    deactivate_plugins('advanced-custom-fields/acf.php');
}
// Disabling the ACF Menu
// Check if ACF free is installed
if (!file_exists(WP_PLUGIN_DIR . '/advanced-custom-fields/acf.php')) {
    // Free plugin not installed
    // Hide the ACF admin menu item.
    add_filter('acf/settings/show_admin', '__return_false');
    // Hide the ACF Updates menu
    add_filter('acf/settings/show_updates', '__return_false', 100);
}

// Enqueue the Bootstrap stylesheet used to style the grid
function ray_events_scripts()
{
    wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css');
    wp_enqueue_style('bootstrap-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css');
    wp_enqueue_style('ray-events-styles', plugins_url('/assets/css/ray-events-styles.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'ray_events_scripts');

/**
 * Register the "eventos" custom post type
 */
function rayevents_setup_post_type()
{
    $post_type_params = [
        'labels' => array(
            'name' => __('Events', 'ray_events'),
            'singular_name' => __('Event', 'ray_events'),
            'menu_name' => _x('Events', 'Admin Menu text', 'ray_events'),
            'add_new_item' => __('Add New Event', 'ray_events'),
        ),
        'description' => "Custom post type for Events",
        'public' => true,
        'hierarchical' => true,
        'show_in_rest' => true,
        'menu_position' => 20,
        'menu_icon' => 'dashicons-tickets',
        'supports' => array('title', 'editor', 'revisions', 'excerpt', 'thumbnail', 'custom-fields'),
        'taxonomies' => array('post_tag'),
        'has_archive' => true,
        'rewrite' => array('slug' => 'events'),
        'query_var' => true

    ];
    register_post_type('events', $post_type_params);
}
add_action('init', 'rayevents_setup_post_type');

/**
 * Use single-events page.
 */
function events_template($single_template)
{
    global $post;
    if ($post->post_type === 'events' && !wp_is_block_theme()) {
        $single_template = plugin_dir_path(__FILE__) . 'public/single-events.php';
    }
    return $single_template;
}
add_filter('single_template', 'events_template');

/**
 * The [upcoming_events] shortcode.
 *
 * Accepts a number and will display a grid of upcoming events.
 *
 * @param array  $atts    Shortcode attributes. Default 3.
 * @param string $content Shortcode content. Default null.
 * @return string Shortcode output.
 */

function upcoming_events_shortcode($atts = [])
{
    // override default attributes with user attributes
    $upcoming_events_atts = shortcode_atts(
        array(
            'display' => 3,
        ),
        $atts
    );
    global $post;
    $content = '';

    // Find todays date in Ymd format.
    $date_now = date('Y-m-d H:i:s');

    // Backup image in case there-s no post thumbnail
    $backup_img = plugins_url('/public/images/pexels-joshsorenson-976866.webp', __FILE__);

    // Query upcoming events
    $args = array(
        'post_type' => 'events',
        'meta_query' => array(
            'key' => 'data_evento',
            'compare' => '>=',
            'value' => $date_now,
            'type' => 'DATETIME',
        ),
        'meta_key' => 'data_evento',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'posts_per_page' => $upcoming_events_atts['display'],
    );
    $events_query = new WP_Query($args);
    if ($events_query->have_posts()) {
        $content .= '<div class="events-grid d-grid gap-3">';
        while ($events_query->have_posts()) {
            $events_query->the_post();

            // Post Thumbnail attributes
            $image_id = get_post_thumbnail_id();
            $image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', TRUE);
            $image_title = get_the_title($image_id);

            // ACF fields
            $data = get_field('data_evento');
            $local = get_field('local_evento');
            $organizacao = get_field('organizador_evento');

            $content .= '
            <div class="card ">
                <div class="card-body d-flex flex-column">
                    <figure class="ratio ratio-1x1 mb-3 text-bg-light">';
            if (has_post_thumbnail()) {
                $content .= '<img src="' . esc_url(get_the_post_thumbnail_url()) . '" class="card-img-top object-fit-cover" alt="' . $image_alt . '" title="' . $image_title . '"/>';
            } else {
                $content .= '<img src="' . $backup_img . '" alt="Photo by Josh Sorenson: group of people raise their hands on stadium" title="Photo by Josh Sorenson" class="card-img-top object-fit-cover"/>';
            }
            $content .= '</figure>
                    <h3 class="card-title">' . esc_html(get_the_title()) . '</h3>';
            if (!empty($data) || !empty($local) || !empty($organizacao)) {
                $content .= '<ul class="p-0 list-unstyled flex-grow-1">';
                if (!empty($data)) {
                    $content .= '<li class="lead m-0 mb-1"><i class="bi bi-calendar"></i> ' . $data . '</li>';
                }
                ;
                if (!empty($local)) {
                    $content .= '<li class="lead m-0 mb-1"><i class="bi bi-geo-alt"></i> ' . $local . '</li>';
                }
                ;
                if (!empty($organizacao)) {
                    $content .= '<li class="lead m-0 mb-1"><i class="bi bi-person-square"></i> ' . $organizacao . '</li>';
                }
                ;
                $content .= '</ul>';
            }
            $content .= '
                    <a href=' . esc_url(get_the_permalink()) . ' class="btn btn-primary">' . __('More info', 'ray_events') . '</a>
                </div>
            </div>';
        }
        $content .= '</div>';
    } else {
        $content .= __("No upcoming events coming soon :(", 'ray_events');
    }
    wp_reset_postdata();
    // return output
    return $content;
}

/**
 * Central location to create all shortcodes.
 */
function upcoming_events_shortcodes_init()
{
    add_shortcode('upcoming_events', 'upcoming_events_shortcode');
}
add_action('init', 'upcoming_events_shortcodes_init');

/**
 * Activate the plugin.
 */
function rayevents_activate()
{
    // Trigger our function that registers the custom post type plugin.
    rayevents_setup_post_type();
    // Clear the permalinks after the post type has been registered.
    flush_rewrite_rules();
    //Register the uninstall hook once upon activation
    register_uninstall_hook(__FILE__, 'rayevents_uninstall');
}
register_activation_hook(__FILE__, 'rayevents_activate');

/**
 * Deactivation hook.
 */
function rayevents_deactivate()
{
    // Unregister the post type, so the rules are no longer in memory.
    unregister_post_type('events');
    // Clear the permalinks to remove our post type's rules from the database.
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'rayevents_deactivate');

/**
 * Uninstall hook.
 */
function rayevents_uninstall()
{
    //	delete custom post type Eventos during unistallation
    $posts = get_posts(array(
        'post_type' => 'events',
        'numberposts' => -1,
        'post_status' => 'any'
    ));

    foreach ($posts as $post) {
        wp_delete_post($post->ID, true);
    }
}
?>