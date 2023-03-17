<?php
/**
* Plugin Name: Scheduler For EventPrime
* Plugin URI: https://plugin.wp.osowsky-webdesign.de/scheduler-for-eventprime
* Description: This plugin offers the possibility to provide events from the EventPrime plugin with a publication period (date and time) so that events can be published automatically.
* Version: 1.0.0
* Requires at least: 5.8.0
* Requires PHP:      8.0
* Author: Silvio Osowsky
* License: GPLv3 or later
* License URI: https://www.gnu.org/licenses/gpl-3.0.html
* Author URI: https://osowsky-webdesign.de/
*/

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
  }

add_action('init', 'register_so_sfe_schedule');
function register_so_sfe_schedule() {
    if (!wp_next_scheduled('so_sfe_schedule_hook')) {
        wp_schedule_event(time(), 'so_sfe_15min_timer', 'so_sfe_schedule_hook');
    }
}

add_filter('cron_schedules', 'so_sfe_cron_schedules');
function so_sfe_cron_schedules($schedules) {
    if (!isset($schedules["so_sfe_15min_timer"])) {
        $schedules["so_sfe_15min_timer"] = array(
            'interval' => 15*60,
            'display' => __('Once every 15 minutes'));
    }
    return $schedules;
}

add_action('so_sfe_schedule_hook', 'so_sfe_schedule_function');
function so_sfe_schedule_function() {
    global $wpdb;
    $date = date('Y-m-d H:i:s', strtotime('-15 minutes'));
    $post_type = "em_event";
    $new_status = "publish";
    
    // Hier wird das Datum und die Uhrzeit von außen gesetzt
    $external_date_time = strtotime('2023-03-20 12:00:00');
    
    // Hier wird die Klasse instanziiert
    $my_class = new My_Class();
    
    // Hier werden alle Beiträge des Typs 'em_event' abgerufen
    $results = $wpdb->get_results( "SELECT * FROM $wpdb->posts WHERE post_type = '$post_type'", OBJECT );
    foreach ( $results as $post ) {
        setup_postdata( $post );
        // Hier wird das Attribut 'em_start_date' aus den Metadaten des Beitrags abgerufen
        $em_start_date = get_post_meta($post->ID, 'em_start_date', true);
        // Hier wird überprüft, ob das Datum innerhalb der nächsten 15 Minuten liegt
        if (strtotime($em_start_date) >= $external_date_time && strtotime($em_start_date) <= $external_date_time + 15*60) {
            // Hier wird die Funktion 'my_method()' der Klasse aufgerufen
            $my_class->my_method($post->ID);
        }
    }
    wp_reset_postdata();
}

// Diese Funktion fügt eine neue Seite im Adminbereich hinzu
function so_sfe_add_admin_page() {
    add_menu_page(
        'EventPrime Veranstaltungen', // Seitentitel
        'EventPrime Veranstaltungen', // Menütitel
        'manage_options', // erforderliche Berechtigung
        'so_sfe_admin_page', // Slug
        'so_sfe_render_admin_page' // Callback-Funktion zum Rendern der Seite
    );
}
add_action('admin_menu', 'so_sfe_add_admin_page');

// Diese Funktion rendert die Seite im Adminbereich
function so_sfe_render_admin_page() {
    global $wpdb;
    $post_type = "em_event";
    
    // Hier werden alle Beiträge des Typs 'em_event' abgerufen
    $results = $wpdb->get_results( "SELECT * FROM $wpdb->posts WHERE post_type = '$post_type'", OBJECT );
    
    // Hier wird die Tabelle gerendert
    echo '<h1>EventPrime Veranstaltungen</h1>';
    echo '<table>';
    echo '<tr>';
    echo '<th>Name der Veranstaltung</th>';
    echo '<th>Status</th>';
    echo '<th>Startdatum</th>';
    echo '<th>Enddatum</th>';
    echo '</tr>';
    
    foreach ( $results as $post ) {
        setup_postdata( $post );
        
        // Hier werden die Metadaten des Beitrags abgerufen
        $em_start_date = get_post_meta($post->ID, 'em_start_date', true);
        $em_end_date = get_post_meta($post->ID, 'em_end_date', true);
        
        // Hier werden die Unix-Zeitstempel in Datum-Zeit-Objekte umgewandelt
        if ($em_start_date) {
            $start_date = date('Y-m-d H:i:s', intval($em_start_date));
        } else {
            $start_date = '';
        }
        
        if ($em_end_date) {
            $end_date = date('Y-m-d H:i:s', intval($em_end_date));
        } else {
            $end_date = '';
        }
        
        // Hier wird eine Zeile für jeden Beitrag gerendert
        echo '<tr>';
        echo '<td>' . esc_html($post->post_title) . '</td>';
        echo '<td>' . esc_html($post->post_status) . '</td>';
        echo '<td>' . esc_html($start_date) . '</td>';
        echo '<td>' . esc_html($end_date) . '</td>';
        echo '</tr>';
    }
    
    wp_reset_postdata();
    
    echo '</table>';
}
// Definiere eine neue WP_List_Table
class Custom_List_Table extends WP_List_Table {
    
    // Definiere Spaltenköpfe
    function get_columns() {
        $columns = array(
            'title' => __('Title'),
            'status' => __('Status'),
            'start_date' => __('Start Date'),
            'end_date' => __('End Date')
        );
        return $columns;
    }
    
    // Definiere die Daten, die in der Tabelle angezeigt werden sollen
    function prepare_items() {
        global $wpdb;
        
        $query = "SELECT * FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish'";
        
        $data = $wpdb->get_results($query);
        
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        $this->items = $data;
    }
    
    // Fülle die Tabellenzellen mit Inhalten
    function column_default($item, $column_name) {
        switch ($column_name) {
            case 'title':
                return esc_html($item->post_title);
            case 'status':
                return esc_html($item->post_status);
            case 'start_date':
                $em_start_date = get_post_meta($item->ID, 'em_start_date', true);
                if ($em_start_date) {
                    return date('Y-m-d H:i:s', intval($em_start_date));
                } else {
                    return '';
                }
            case 'end_date':
                $em_end_date = get_post_meta($item->ID, 'em_end_date', true);
                if ($em_end_date) {
                    return date('Y-m-d H:i:s', intval($em_end_date));
                } else {
                    return '';
                }
            default:
                return '';
        }
    }
    
}

// Erstelle eine neue Instanz der WP_List_Table
function custom_table() {
    $table = new Custom_List_Table();
    $table->prepare_items();
    $table->display();
}

// Rufe die Funktion auf, um die Tabelle zu erstellen
custom_table();