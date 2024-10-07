<?php
/**
 * Template Name: Cities Table
 */

get_header();

// Custom action hook before the table
do_action('before_cities_table');

// Add a search field for cities
?>
<form id="city-search-form">
    <input type="text" id="city-search" placeholder="Search for a city...">
    <button type="submit">Search</button>
</form>

<div id="cities-table">
    <?php display_cities_table(); ?>
</div>

<?php
// Custom action hook after the table
do_action('after_cities_table');

get_footer();


// function display_cities_table($search_term = '') {
//     global $wpdb;
//     $query = "SELECT * FROM {$wpdb->prefix}posts WHERE post_type = 'cities' AND post_status = 'publish'";
//     if ($search_term) {
//         $query .= $wpdb->prepare(" AND post_title LIKE %s", '%' . $wpdb->esc_like($search_term) . '%');
//     }
//     $cities = $wpdb->get_results($query);

//     echo '<table>
//         <thead>
//             <tr>
//                 <th>Country</th>
//                 <th>City</th>
//                 <th>Temperature</th>
//             </tr>
//         </thead>
//         <tbody>';
    
//     foreach ($cities as $city) {
//         $country_terms = get_the_terms($city->ID, 'country');
//         $country = $country_terms ? $country_terms[0]->name : 'N/A';
//         $latitude = get_post_meta($city->ID, '_city_latitude', true);
//         $longitude = get_post_meta($city->ID, '_city_longitude', true);
//         // $temperature = get_city_temperature($latitude, $longitude);
//         $temperature = "TEST";

//         echo '<tr>
//             <td>' . esc_html($country) . '</td>
//             <td>' . esc_html($city->post_title) . '</td>
//             <td>' . esc_html($temperature) . '°C</td>
//         </tr>';
//     }

//     echo '</tbody></table>';
// }
