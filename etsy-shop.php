<?php
/**
 * @package Etsy-Shop
 */
/*
Plugin Name: Etsy Shop
Plugin URI: http://wordpress.org/extend/plugins/etsy-shop/
Description: Inserts Etsy products in page or post using bracket/shortcode method.
Author: Frédéric Sheedy
Version: 0.9.1
*/

/*  
 * Copyright 2011-2012  Frédéric Sheedy  (email : sheedf@gmail.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as 
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/* Roadmap to version 1.x
 * TODO: touch() file in tmp folder
 * TODO: reset cache function
 * TODO: allow more than 25 items
 * TODO: customize currency
 * TODO: get Etsy translations
 */

define( 'ETSY_SHOP_VERSION',  '0.9.1');
define( 'ETSY_SHOP_CACHE_LIFE',  21600 ); // 6 hours in seconds

// load translation
add_action( 'init', 'etsy_shop_load_translation_file' );
 
function etsy_shop_load_translation_file() {
    $plugin_path = plugin_basename( dirname( __FILE__ ) .'/translations' );
    load_plugin_textdomain( 'etsyshop', false, $plugin_path );
}

function etsy_shop_activate() {
    // version upgrade
    add_option( 'etsy_shop_version', ETSY_SHOP_VERSION );

    $etsy_shop_DB_version = get_option( 'etsy_shop_version' );
    if ( $etsy_shop_DB_version != ETSY_SHOP_VERSION) {

        // upgrade logic here

        // update the version value
        update_option( 'etsy_shop_version', ETSY_SHOP_VERSION );
    }
}

// plugin activation
register_activation_hook( __FILE__, 'etsy_shop_activate' );


// process the content of a page or post
add_filter( 'the_content', 'etsy_shop_post' );
add_filter( 'the_excerpt','etsy_shop_post' );

// complements of YouTube Brackets
function etsy_shop_post( $the_content ) {
    // if API Key exist
    if ( get_option( 'etsy_shop_api_key' ) ) {
        $etsy_start_tag = "[etsy-include=";
        $etsy_end_tag = "]";
        
        $spos = strpos( $the_content, $etsy_start_tag );
        if ( $spos !== false ) {
            $epos = strpos( $the_content, $etsy_end_tag, $spos );
            $spose = $spos + strlen( $etsy_start_tag );
            $slen = $epos - $spose;
            $tagargs = substr( $the_content, $spose, $slen );

            $args = explode( ";", $tagargs );
            if ( sizeof( $args ) > 1 ) {
                $etsy_shop_id = $args[0];
                $etsy_section_id = $args[1];
                                
                if ( $etsy_shop_id != '' || $etsy_section_id != '' ) {
                    // generate listing for shop section
                    $listings = etsy_shop_getShopSectionListings( $etsy_shop_id, $etsy_section_id );
                    if ( !get_option( 'etsy_shop_debug_mode' ) ) {
                        if ( !is_wp_error( $listings ) ) {
                            $tags = '<table class="etsy-shop-listing-table"><tr>';
                            $n = 1;
                            foreach ( $listings->results as $result ) {
                                $listing_html = etsy_shop_generateListing( $result->listing_id, $result->title, $result->state, $result->price, $result->currency_code, $result->quantity, $result->url, $result->Images[0]->url_170x135 );
                                if ( $listing_html !== false ) {
                                    $tags = $tags.'<td class="etsy-shop-listing">'.$listing_html.'</td>';
                                    $n++;
                                    if ( $n == 4 ) {
                                        $tags = $tags.'</tr><tr>';
                                        $n = 1;
                                    }
                                }
                            }
                            $tags = $tags.'</tr></table>';

                            $new_content = substr( $the_content,0,$spos );
                            $new_content .= $tags;
                            $new_content .= substr( $the_content,( $epos+1 ) );
                        } else {
                            $new_content = $listings->get_error_message();
                        }
                    } else {
                        print_r( '<h2>' . __( 'Etsy Shop Debug Mode', 'etsyshop' ) . '</h2>' );
                        print_r( $listings );
                    }
                } else {
                    // must have 2 arguments
                    $new_content = "Etsy Shop: empty arguments";
                }
            } else {
                // must have 2 arguments
                $new_content = "Etsy Shop: missing arguments";
            }
            
            // other bracket to parse?
            if ( $epos+1 < strlen( $the_content ) ) {
                $new_content = etsy_shop_post( $new_content );
            }

            return $new_content;
        } else {
            return $the_content;
        }
    } else {
        // no API Key set, return the content
        return $the_content;
    }
}

function etsy_shop_getShopSectionListings( $etsy_shop_id, $etsy_section_id ) {
    $etsy_cache_file = dirname( __FILE__ ).'/tmp/'.$etsy_shop_id.'-'.$etsy_section_id.'_cache.json';
    
    // if no cache file exist
    if (!file_exists( $etsy_cache_file ) or ( time() - filemtime( $etsy_cache_file ) >= ETSY_SHOP_CACHE_LIFE ) ) {
        $reponse = etsy_shop_api_request( "shops/$etsy_shop_id/sections/$etsy_section_id/listings/active", '&includes=Images' );
        if ( !is_wp_error( $reponse ) ) {
            // if request OK
            $tmp_file = $etsy_cache_file.rand().'.tmp';
            file_put_contents( $tmp_file, $reponse );
            rename( $tmp_file, $etsy_cache_file );
        } else {
            // return WP_Error
            return $reponse;
        }
    } else {
        // read cache file
        $reponse = file_get_contents( $etsy_cache_file );
    }
    
    $data = json_decode( $reponse );
    return $data;
}

function etsy_shop_getShopSection( $etsy_shop_id, $etsy_section_id ) {
    $reponse = etsy_shop_api_request( "shops/$etsy_shop_id/sections/$etsy_section_id" );
    if ( !is_wp_error( $reponse ) ) {
        $data = json_decode( $reponse );
    } else {
        // return WP_Error
        return $reponse;
    }
    
    return $data;
}

function etsy_shop_testAPIKey() {
    $reponse = etsy_shop_api_request( 'listings/active', '&limit=1&offset=0' );
    if ( !is_wp_error( $reponse ) ) {
        $data = json_decode( $reponse );
    } else {
        // return WP_Error
        return $reponse;
    }
    
    return $data;
}

function etsy_shop_api_request( $etsy_request, $args = NULL ) {
    $etsy_api_key = get_option( 'etsy_shop_api_key' );
    $url = "http://openapi.etsy.com/v2/$etsy_request?api_key=" . $etsy_api_key . $args;
    
    $request = wp_remote_request( $url );
    
    if ( !is_wp_error( $request ) ) {
        if ( $request['response']['code'] == 200 ) {
            $request_body = $request['body'];
        } else {
            return  new WP_Error( 'etsy-shop', __( 'Etsy Shop: API reponse should be HTTP 200', 'etsyshop' ) );
        }
    } else {
        return  new WP_Error( 'etsy-shop', __( 'Etsy Shop: Error on API Request', 'etsyshop' ) );
    }
    
    return $request_body;
}

function etsy_shop_generateListing($listing_id, $title, $state, $price, $currency_code, $quantity, $url, $url_170x135) {
    if ( strlen( $title ) > 18 ) {
        $title = substr( $title, 0, 25 );
        $title .= "...";
    }
    
    // if the Shop Item is active
    if ( $state == 'active' ) {
        $state = __( 'Available', 'etsyshop' );
        
        $script_tags =  '
            <div class="etsy-shop-listing-card" id="' . $listing_id . '">
                <a title="' . $title . '" href="' . $url . '" class="etsy-shop-listing-thumb">
                    <img alt="' . $title . '" src="' . $url_170x135 . '">          
                </a>
                <div class="etsy-shop-listing-detail">
                    <p class="etsy-shop-listing-title">
                        <a title="' . $title . '" href="' . $url . '">'.$title.'</a>
                    </p>
                    <p class="etsy-shop-listing-maker">
                        <a title="' . $title . '" href="' . $url . '">'.$state.'</a>
                    </p>
                </div>
                <p class="etsy-shop-listing-price">$'.$price.' <span class="etsy-shop-currency-code">'.$currency_code.'</span></p>
            </div>'; 
            
        return $script_tags;
    } else {
        return false;
    }
}

// Custom CSS

add_action( 'wp_print_styles', 'etsy_shop_css' );

function etsy_shop_css() {
    $link = plugins_url( 'etsy-shop.css', __FILE__ );
    wp_register_style( 'etsy_shop_style', $link );
    wp_enqueue_style( 'etsy_shop_style' );
}


// Options Menu
add_action( 'admin_menu', 'etsy_shop_menu' );

function etsy_shop_menu() {
        add_options_page( __( 'Etsy Shop Options', 'etsyshop' ), __( 'Etsy Shop', 'etsyshop' ), 'manage_options', basename( __FILE__ ), 'etsy_shop_optionsPage' );
}

function etsy_shop_optionsPage() {
    // did the user is allowed?
    if ( !current_user_can( 'manage_options' ) )  {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'etsyshop' ) );
    }

    if ( isset( $_POST['submit'] ) ) {
        // did the user enter an API Key?
        if ( isset( $_POST['etsy_shop_api_key'] ) ) {
            $etsy_shop_api_key = wp_filter_nohtml_kses( $_POST['etsy_shop_api_key'] );
            update_option( 'etsy_shop_api_key', $etsy_shop_api_key );

            // and remember to note the update to user
            $updated = true;
        }
    
        // did the user enter Debug mode?
        if ( isset( $_POST['etsy_shop_debug_mode'] ) ) {
            $etsy_shop_debug_mode = wp_filter_nohtml_kses( $_POST['etsy_shop_debug_mode'] );
            //die($etsy_shop_debug_mode);
            update_option( 'etsy_shop_debug_mode', $etsy_shop_debug_mode );

            // and remember to note the update to user
            $updated = true;
        }else {
            $etsy_shop_debug_mode = 0;
            //die($etsy_shop_debug_mode);
            update_option( 'etsy_shop_debug_mode', $etsy_shop_debug_mode );

            // and remember to note the update to user
            $updated = true;
        }
    }

    // grab the Etsy API key
    if( get_option( 'etsy_shop_api_key' ) ) {
        $etsy_shop_api_key = get_option( 'etsy_shop_api_key' );
    } else {
        add_option( 'etsy_shop_api_key', '' );
    }
    
    // grab the Etsy Debug Mode
    if( get_option( 'etsy_shop_debug_mode' ) ) {
        $etsy_shop_debug_mode = get_option( 'etsy_shop_debug_mode' );
    } else {
        add_option( 'etsy_shop_debug_mode', '0' );
    }

    if ( $updated ) {
        echo '<div class="updated fade"><p><strong>'. __( 'Options saved.', 'etsyshop' ) .'</strong></p></div>';
    }

    // print the Options Page
    ?>
    <div class="wrap">
        <div id="icon-options-general" class="icon32"><br /></div><h2><?php _e( 'Etsy Shop Options', 'etsyshop' ); ?></h2>
        <form name="etsy_shop_options_form" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">
                        <label for="etsy_shop_api_key"></label><?php _e('Etsy API Key', 'etsyshop'); ?>
                    </th>
                    <td>
                        <input id="etsy_shop_api_key" name="etsy_shop_api_key" type="text" size="25" value="<?php echo get_option( 'etsy_shop_api_key' ); ?>" class="regular-text code" />
                                    <?php if ( !is_wp_error( etsy_shop_testAPIKey()) ) { ?>
                                        <span id="etsy_shop_api_key_status" style="color:green;font-weight:bold;">Your API Key is valid</span>
                                    <?php } elseif ( get_option('etsy_shop_api_key') ) { ?>
                                        <span id="etsy_shop_api_key_status" style="color:red;font-weight:bold;">You API Key is invalid</span>
                                    <?php } ?>
                                    <p class="description">
                                    <?php echo sprintf( __('You may get an Etsy API Key by <a href="%1$s">Creating a new Etsy App</a>', 'etsyshop' ), 'http://www.etsy.com/developers/register' ); ?></p>
                    </td>
                 </tr>
                 <tr valign="top">
                    <th scope="row">
                        <label for="etsy_shop_api_key"></label><?php _e('Debug Mode', 'etsyshop'); ?></th>
                            <td>
                                <input id="etsy_shop_debug_mode" name="etsy_shop_debug_mode" type="checkbox" value="1" <?php checked( '1', get_option( 'etsy_shop_debug_mode' ) ); ?> />
                                    <p class="description">
                                    <?php echo __( 'Useful if you wan\'t to post a bug on the forum', 'etsyshop' ); ?>
                                    </p>
                            </td>
                 </tr>
                        <tr valign="top">
                                <th scope="row"><?php _e('Cache Status', 'etsyshop'); ?></th>
                                <td>
                                    <?php if (get_option('etsy_shop_api_key')) { ?>
                                    <table class="wp-list-table widefat fixed">
                                        <thead>
                                        <tr>
                                            <th>Shop Section</th>
                                            <th>Filename</th>
                                            <th>Last update</th>
                                        </tr>
                                        </thead>
                                        <?php 
                                $files = glob( dirname( __FILE__ ).'/tmp/*.json' );
                                $time_zone = get_option('timezone_string');
                                date_default_timezone_set( $time_zone );
                                foreach ($files as $file) {
                                    // downgrade to support PHP 5.2.4
                                    //$etsy_shop_section = explode( "-", strstr(basename( $file ), '_cache.json', true ) );
                                    $etsy_shop_section = explode( "-", substr( basename( $file ), 0, strpos( basename( $file ), '_cache.json' ) ) );
                                    $etsy_shop_section_info = etsy_shop_getShopSection($etsy_shop_section[0], $etsy_shop_section[1]);
                                    if ( !is_wp_error( $etsy_shop_section_info ) ) {
                                        echo '<tr><td>' . $etsy_shop_section[0] . ' / ' . $etsy_shop_section_info->results[0]->title . '</td><td>' . basename( $file ) . '</td><td>' .  date( "Y-m-d H:i:s", filemtime( $file ) ) . '</td></tr>';
                                    } else {
                                        echo '<tr><td>' . $etsy_shop_section[0] . ' / <span style="color:red;">Error on API Request</span>' . '</td><td>' . basename( $file ) . '</td><td>' .  date( "Y-m-d H:i:s", filemtime( $file ) ) . '</td></tr>';
                                    }
                                }
                                    ?></table><?php } else { _e('You must enter your Etsy API Key to view cache status!', 'etsyshop'); } ?>
                                <p class="description"><?php _e( 'You may reset cache a any time by deleting files in tmp folder of the plugin.', 'etsyshop' ); ?></p>
                                </td>
                        </tr>
        </table>
            
        <h3 class="title"><?php _e( 'Need more features?', 'etsyshop' ); ?></h3>
        <p><?php echo sprintf( __( 'Please open a <a href="%1$s">new topic</a> on Wordpress.org Forum. This is your only way to let me know!', 'etsyshop' ), 'http://wordpress.org/support/plugin/etsy-shop' ); ?></p>

        <p class="submit">
                <input type="submit" name="submit" id="submit" class="button-primary" value="<?php _e( 'Save Changes', 'etsyshop' ); ?>" />
        </p>
    
        </form>
    </div>
<?php
}

// admin warning
if ( is_admin() ) {
    etsy_shop_warning();
}

function etsy_shop_warning() {
    if ( !get_option( 'etsy_shop_api_key' ) ) {
        function etsy_shop__api_key_warning() {
            echo "<div id='etsy-shop-warning' class='updated fade'><p><strong>".__( 'Etsy Shop is almost ready.', 'etsyshop' )."</strong> ".sprintf( __( 'You must <a href="%1$s">enter your Etsy API key</a> for it to work.', 'etsyshop' ), 'options-general.php?page=etsy-shop.php' )."</p></div>";
        }
        
        add_action( 'admin_notices', 'etsy_shop__api_key_warning' );
    }
}

function etsy_shop_plugin_action_links( $links, $file ) {
    if ( $file == plugin_basename( dirname( __FILE__ ).'/etsy-shop.php' ) ) {
        $links[] = '<a href="options-general.php?page=etsy-shop.php">' . __( 'Settings' ) . '</a>';
    }
    
    return $links;
}

// add Settings link
add_filter( 'plugin_action_links', 'etsy_shop_plugin_action_links', 10, 2 );

?>
