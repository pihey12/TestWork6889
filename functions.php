<?php
// Enqueue Styles
function storefront_child_enqueue_styles() {
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
}
add_action('wp_enqueue_scripts', 'storefront_child_enqueue_styles');

// Register Custom Post Type: Cities
function create_cities_post_type() {
    $labels = array(
        'name' => 'Cities',
        'singular_name' => 'City',
        'menu_name' => 'Cities',
        'name_admin_bar' => 'City',
        'add_new' => 'Add New',
        'add_new_item' => 'Add New City',
        'new_item' => 'New City',
        'edit_item' => 'Edit City',
        'view_item' => 'View City',
        'all_items' => 'All Cities',
        'search_items' => 'Search Cities',
        'not_found' => 'No cities found.',
        'not_found_in_trash' => 'No cities found in Trash.',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'thumbnail'),
        'show_in_rest' => true,
    );

    register_post_type('cities', $args);
}
add_action('init', 'create_cities_post_type');

// Register Custom Taxonomy: Countries
function create_countries_taxonomy() {
    $labels = array(
        'name' => 'Countries',
        'singular_name' => 'Country',
        'search_items' => 'Search Countries',
        'all_items' => 'All Countries',
        'edit_item' => 'Edit Country',
        'update_item' => 'Update Country',
        'add_new_item' => 'Add New Country',
        'new_item_name' => 'New Country Name',
        'menu_name' => 'Countries',
    );

    $args = array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'country'),
    );

    register_taxonomy('country', array('cities'), $args);
}
add_action('init', 'create_countries_taxonomy');

// Add Meta Box for Latitude and Longitude
function add_city_meta_boxes() {
    add_meta_box(
        'city_location',
        'City Location',
        'city_location_callback',
        'cities',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_city_meta_boxes');

function city_location_callback($post) {
    $latitude = get_post_meta($post->ID, '_city_latitude', true);
    $longitude = get_post_meta($post->ID, '_city_longitude', true);
    ?>
    <label for="city_latitude">Latitude:</label>
    <input type="text" id="city_latitude" name="city_latitude" value="<?php echo esc_attr($latitude); ?>" />
    <br/>
    <label for="city_longitude">Longitude:</label>
    <input type="text" id="city_longitude" name="city_longitude" value="<?php echo esc_attr($longitude); ?>" />
    <?php
}

function save_city_meta($post_id) {
    if (array_key_exists('city_latitude', $_POST)) {
        update_post_meta($post_id, '_city_latitude', sanitize_text_field($_POST['city_latitude']));
    }
    if (array_key_exists('city_longitude', $_POST)) {
        update_post_meta($post_id, '_city_longitude', sanitize_text_field($_POST['city_longitude']));
    }
}
add_action('save_post', 'save_city_meta');

// Widget: City Temperature
class City_Temperature_Widget extends WP_Widget {
    function __construct() {
        parent::__construct(
            'city_temperature_widget',
            'City Temperature Widget',
            array('description' => 'Displays a city and its current temperature.')
        );
    }

    public function widget($args, $instance) {
        echo $args['before_widget'];
    
        // Set a static city name for testing
        $city = $instance['city'];
    
        echo $args['before_title'] . $city . $args['after_title'];
    
        // Fetch latitude and longitude from post meta
        $city_post = get_page_by_title($city, OBJECT, 'cities');
        if ($city_post) {
            $latitude = get_post_meta($city_post->ID, '_city_latitude', true);
            $longitude = get_post_meta($city_post->ID, '_city_longitude', true);
    
            // Make API call to OpenWeatherMap
            $api_key = '2794ce5526f90df5e1cb6041997ad092';
            $response = wp_remote_get("https://api.openweathermap.org/data/2.5/weather?lat={$latitude}&lon={$longitude}&appid={$api_key}&units=metric");
    
            if (is_array($response) && !is_wp_error($response)) {
                $body = json_decode($response['body']);
                if (isset($body->main->temp)) {
                    $temperature = $body->main->temp;
                    echo "<p>Current Temperature: {$temperature}°C</p>";
                } else {
                    echo "<p>Temperature data not available.</p>";
                }
            } else {
                echo "<p>Unable to retrieve weather data.</p>";
            }
        } else {
            echo "<p>City not found.</p>";
        }
    
        echo $args['after_widget'];
    }

    public function form($instance) {
        $city = !empty($instance['city']) ? $instance['city'] : '';
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('city'); ?>">City:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('city'); ?>" name="<?php echo $this->get_field_name('city'); ?>" type="text" value="<?php echo esc_attr($city); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['city'] = (!empty($new_instance['city'])) ? strip_tags($new_instance['city']) : '';
        return $instance;
    }
}

function register_city_temperature_widget() {
    register_widget('City_Temperature_Widget');
}
add_action('widgets_init', 'register_city_temperature_widget');

// AJAX: Search Cities
function ajax_search_cities() {
    $search_term = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    ob_start();
    display_cities_table($search_term);
    wp_send_json_success(ob_get_clean());
}
add_action('wp_ajax_search_cities', 'ajax_search_cities');
add_action('wp_ajax_nopriv_search_cities', 'ajax_search_cities');

//For Displaying the Cities Table
function display_cities_table($search_term = '') {
    global $wpdb;
    $query = "SELECT * FROM {$wpdb->prefix}posts WHERE post_type = 'cities' AND post_status = 'publish'";
    if ($search_term) {
        $query .= $wpdb->prepare(" AND post_title LIKE %s", '%' . $wpdb->esc_like($search_term) . '%');
    }
    $cities = $wpdb->get_results($query);

    echo '<table>
        <thead>
            <tr>
                <th>Country</th>
                <th>City</th>
                <th>Temperature</th>
            </tr>
        </thead>
        <tbody>';
    
    foreach ($cities as $city) {
        $country_terms = get_the_terms($city->ID, 'country');
        $country = $country_terms ? $country_terms[0]->name : 'N/A';
        $latitude = get_post_meta($city->ID, '_city_latitude', true);
        $longitude = get_post_meta($city->ID, '_city_longitude', true);
        $temperature = get_city_temperature($latitude, $longitude);

        echo '<tr>
            <td>' . esc_html($country) . '</td>
            <td>' . esc_html($city->post_title) . '</td>
            <td>' . esc_html($temperature) . '°C</td>
        </tr>';
    }

    echo '</tbody></table>';
}

//For Getting the Temperature of the City with OpenWeatherMap API
function get_city_temperature($latitude, $longitude) {
    $api_key = '2794ce5526f90df5e1cb6041997ad092'; // Replace with your actual API key
    $response = wp_remote_get("https://api.openweathermap.org/data/2.5/weather?lat={$latitude}&lon={$longitude}&appid={$api_key}&units=metric");

    if (is_array($response) && !is_wp_error($response)) {
        $body = json_decode($response['body']);
        return $body->main->temp;
    }
    return 'N/A';
}

// Enqueue AJAX Script
function enqueue_ajax_script() {
    wp_enqueue_script('ajax-search', get_stylesheet_directory_uri() . '/js/ajax-search.js', array('jquery'), null, true);
    wp_localize_script('ajax-search', 'ajaxurl', admin_url('admin-ajax.php'));
}
add_action('wp_enqueue_scripts', 'enqueue_ajax_script');