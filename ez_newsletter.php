<?php

/**
 * Plugin Name:       Newsletter Form
 * Plugin URI:
 * Description:       Newsletter form in your WordPress website
 * Version:           1.0.0
 * Requires at least: 5.7
 * Requires PHP:      7.4
 * Author:            Ewelina Ziobro
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

//define( 'NEWSLETTER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Tworzenie tabeli przy aktywacji wtyczki
register_activation_hook( __FILE__, 'ez_newsletter_create_table' );
function ez_newsletter_create_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'ez_newsletter_subscribers';

    $charset_collate = $wpdb->get_charset_collate();

    // Sprawdzenie, czy tabela już istnieje
    $table_exists = $wpdb->get_var( $wpdb->prepare(
        "SHOW TABLES LIKE %s", $table_name
    ) );

    if ( ! $table_exists ) {
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            email varchar(100) NOT NULL,
            date_saved datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY email (email)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
}

// obsługiwanie do dezinstalacji wtyczki
register_uninstall_hook( __FILE__, 'ez_uninstall_newsletter_plugin' );
function ez_uninstall_newsletter_plugin() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ez_newsletter_subscribers';

    // Sprawdzenie czy tabela istnieje
    $table_exists = $wpdb->get_var( $wpdb->prepare(
        "SHOW TABLES LIKE %s", $table_name
    ) );

    if ( $table_exists ) {
        // Sprawdzenie, czy tabela jest pusta
        $count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
        if ( $count == 0 ) {
            $wpdb->query( "DROP TABLE IF EXISTS $table_name" );
        }
    }
}

//definiowanie zakładki dla subskrybentów w panelu administratora w WP
add_action('admin_menu', 'ez_newsletter_add_admin_menu');
function ez_newsletter_add_admin_menu() {
    add_menu_page(
        'Subskrybenci Newslettera',
        'Subskrybenci Newslettera',
        'manage_options',
        'ez-newsletters-subscribers',
        'ez_newsletter_display_subscribers',
        'dashicons-email',
        26
    );
}

// Funkcja do generowania linków sortowania
/*function ez_sort_link($column, $label, $orderby, $order) {
    $new_order = ($orderby === $column && $order === 'ASC') ? 'desc' : 'asc';
    $arrow = '';
    if ($orderby === $column) $arrow = $order === 'ASC' ? ' ↑' : ' ↓';
    return '<a href="' . admin_url("admin.php?page=ez-newsletter-subscribers&orderby=$column&order=$new_order") . '">' . $label . $arrow . '</a>';
}*/

// Funkcja do generowania linków sortowania w stylu WP
function ez_sort_link($column, $label, $orderby, $order) {
    $new_order = ($orderby === $column && $order === 'ASC') ? 'desc' : 'asc';

    // Dodanie klas WordPressa
    $classes = ['sortable'];
    if ($orderby === $column) {
        $classes[] = 'sorted';
        $classes[] = strtolower($order);
    }

    // HTML strzałek w stylu WP
    $arrows = '<span class="sorting-indicators">'
            . '<span class="sorting-indicator asc" aria-hidden="true"></span>'
            . '<span class="sorting-indicator desc" aria-hidden="true"></span>'
            . '</span>';

    $url = admin_url("admin.php?page=ez-newsletters-subscribers&orderby=$column&order=$new_order");

    return '<a href="' . esc_url($url) . '" class="' . esc_attr(implode(' ', $classes)) . '">'
         . esc_html($label)
         . $arrows
         . '</a>';
}

// Wyświetlenie listy subskrybentów
function ez_newsletter_display_subscribers() {
    if ( ! current_user_can('manage_options') ) {
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'ez_newsletter_subscribers';

    // Obsługa usuwania maila
    if ( isset($_GET['delete']) && is_numeric($_GET['delete']) ) {
        $delete_id = intval($_GET['delete']);
        $wpdb->delete($table_name, array('id' => $delete_id));
        echo '<div class="notice notice-success is-dismissible"><p>Usunięto email z listy.</p></div>';
    }

    // parametry dla sortowania
    $sortable_columns = ['email', 'date_added'];
    $orderby = isset($_GET['orderby']) && in_array($_GET['orderby'], $sortable_columns) ? $_GET['orderby'] : 'id';
    $order = isset($_GET['order']) && $_GET['order'] === 'desc' ? 'DESC' : 'ASC';

    // parametry dla paginacji
    $per_page = 25;
    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($paged - 1) * $per_page;

    // Pobranie liczby wszystkich subskrybentów
    $total_subscribers = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

    // Pobranie listy subskrybentów z uwzględnieniem paginacji i sortowania
    $subscribers = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d",
        $per_page, $offset
    ));

    echo '<div class="wrap">';
    echo '<h1>Subskrybenci Newslettera</h1>';


    if ( $subscribers ) {

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>
            <th>Nr</th>
            <th>'. ez_sort_link('email', 'Email', $orderby, $order) .'</th>
            <th>'.ez_sort_link('date_saved', 'Data zapisu', $orderby, $order).'</th>
            <th>Akcja</th>
        </tr></thead>';
        echo '<tbody>';

        $counter = 1;
        foreach ( $subscribers as $sub ) {
            echo '<tr>';
            echo '<td>' . $counter . '</td>';
            echo '<td>' . esc_html($sub->email) . '</td>';
            echo '<td>' . esc_html($sub->date_saved) . '</td>';
            echo '<td><a href="' . admin_url('admin.php?page=ez-newsletters-subscribers&delete=' . $sub->id) . '" onclick="return confirm(\'Czy na pewno chcesz usunąć tego subskrybenta?\')">Usuń</a></td>';
            echo '</tr>';
            $counter++;
        }
        echo '</tbody></table>';


        // Wyświetlenie paginacji
        $total_pages = ceil($total_subscribers / $per_page);
        if ($total_pages > 1) {
            echo '<div class="tablenav"><div class="tablenav-pages">';
            for ($i = 1; $i <= $total_pages; $i++) {
                if ($i == $paged) {
                    echo '<span class="page-numbers current">' . $i . '</span> ';
                } else {
                    $link = admin_url("admin.php?page=ez-newsletters-subscribers&paged=$i&orderby=$orderby&order=$order");
                    echo '<a class="page-numbers" href="' . $link . '">' . $i . '</a> ';
                }
            }
            echo '</div></div>';
        }


    } else {
        echo '<p>Brak zapisanych subskrybentów.</p>';
    }

    echo '</div>';
}


// Dodanie CSS do stron na których występuje shortcode
add_action('wp_enqueue_scripts', 'ez_enqueue_styles');
function ez_enqueue_styles() {

    global $post;
    if ( isset($post->post_content) && has_shortcode($post->post_content, 'ez_newsletter_form') ) {
        wp_enqueue_style(
            'ez-newsletter-style',
            plugin_dir_url(__FILE__) . 'assets/ez_newsletter.css',
            array(),
            '1.0'
        );
        wp_enqueue_script(
            'ez-newsletter-js',
            plugin_dir_url(__FILE__) . 'assets/ez_newsletter.js',
            array(),
            '1.0',
            true
        );
        wp_localize_script('ez-newsletter-js', 'ez_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php')
        ));
        //wp_enqueue_script('jquery');
    }
}

// Ładowanie stylów w panelu admina (tylko na stronie subskrybentów)
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook !== 'toplevel_page_ez-newsletters-subscribers') {
        return; // Ładujemy tylko na naszej stronie wtyczki
    }
    wp_enqueue_style(
        'ez-newsletter-admin-styles',
        plugin_dir_url(__FILE__) . 'assets/ez_newsletter.css',
        [],
        '1.0'
    );
});


add_shortcode( 'ez_newsletter_form', 'ez_show_newsletter_form' );
function ez_show_newsletter_form( $atts ) {

    //sprawdź czy użytkownik jest zalogowany i czy na obecnego maila już się zapisał do formularza
    global $wpdb;

    if ( is_user_logged_in() ) {

        $current_user = wp_get_current_user();
        $user_email = $current_user->user_email;

        // Sprawdzenie, czy email zalogowanego uzytkownika istnieje w tabeli newslettera
        $table_name = $wpdb->prefix . 'ez_newsletter_subscribers';
        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE email = %s",
            $user_email
        ) );

        if ( $exists ) {
            // E-mail jest już zapisany, więc nic nie pokazujemy
            return '';
        }

    }

    // Sprawdzenie cookie dla niezalogowanych
    if ( isset($_COOKIE['ez_newsletter_subscribed']) && $_COOKIE['ez_newsletter_subscribed'] == '1' ) {
        return ''; // nic nie wyświetlaj
    }

    ob_start();

    //wyświetl templatkę formularza
    include 'templates/ez-newsletter-form.php';

    return ob_get_clean();

}

// Obsługa zapisu osób do newslettera AJAX zapisu
add_action('wp_ajax_ez_newsletter_save_subscriber', 'ez_newsletter_save_subscriber');
add_action('wp_ajax_nopriv_ez_newsletter_save_subscriber', 'ez_newsletter_save_subscriber');

function ez_newsletter_save_subscriber() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ez_newsletter_subscribers';

    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';

    // Walidacja emaila
    if( ! is_email($email) ) {
        wp_send_json_error('Niepoprawny adres email.');
    }

    // Sprawdzenie czy email już istnieje
    $exists = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE email = %s", $email
    ) );

    if( $exists ) {
        wp_send_json_error('Ten email jest już zapisany do newslettera.');
    }

    // Dodanie do bazy
    $inserted = $wpdb->insert(
        $table_name,
        array(
            'email' => $email,
            'date_saved' => current_time('mysql'),
        ),
        array('%s', '%s')
    );

    // Ustaw cookie na 1 rok dla użytkowników zapisanych w tej przeglądarce
    setcookie( 'ez_newsletter_subscribed', '1', time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );


    if( $inserted ) {
        wp_send_json_success('Dziękujemy za zapisanie się do newslettera!');
    } else {
        wp_send_json_error('Wystąpił błąd, spróbuj ponownie.');
    }
}