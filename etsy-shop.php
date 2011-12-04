<?php
/**
 * @package Etsy-Shop
 */
/*
Plugin Name: Etsy Shop
Description: Inserts Etsy Shop in post or page using bracket/shortcode method.
Author: Frédéric Sheedy
Version: 0.8
*/

/*  
 * Copyright 2011  Frédéric Sheedy  (email : sheedf@gmail.com)
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

define( 'ETSY_SHOP_CACHE_LIFE',  21600 ); //6 hours in seconds

// Load translation
add_action( 'init', 'etsy_shop_load_translation_file' );
 
function etsy_shop_load_translation_file() {
    $plugin_path = plugin_basename( dirname( __FILE__ ) .'/translations' );
    load_plugin_textdomain( 'etsyshop', false, $plugin_path );
}

// process the content of a post or page
add_filter( 'the_content', 'etsy_shop_post' );
add_filter( 'the_excerpt','etsy_shop_post' );

// complements of YouTube Brackets
function etsy_shop_post( $the_content ) {
        // if API Key is set
        if ( get_option( 'etsy_shop_api_key' ) ) {
                $etsy_start_tag = "[etsy-include=";
                $etsy_end_tag = "]";

                $spos = strpos($the_content, $etsy_start_tag);
                if ( $spos !== false ) {
                        $epos = strpos($the_content, $etsy_end_tag, $spos);
                        $spose = $spos + strlen($etsy_start_tag);
                        $slen = $epos - $spose;
                        $tagargs = substr($the_content, $spose, $slen);

                        $args = explode(";", $tagargs);
                        if (sizeof($args) == 2) {
                                $etsy_shop_id = $args[0];
                                $etsy_section_id = $args[1];
                                // Generate listing for shop section
                                $listings = etsy_shop_findAllShopSectionListings($etsy_shop_id, $etsy_section_id);
                                $tags = '<table class="listing-table"><tr>';
                                $n = 1;
                                foreach ($listings->results as $result) {
                                        $tags = $tags.'<td>'.etsy_shop_generate_listing($result->listing_id, $result->title, $result->state, $result->price, $result->currency_code, $result->quantity, $result->url).'</td>';
                                        $n++;
                                        if ($n == 4) {
                                                $tags = $tags.'</tr><tr>';
                                                $n = 1;
                                        }
                                }
                                $tags = $tags.'</tr></table>';

                                $new_content = substr($the_content,0,$spos);
                                $new_content .= $tags;
                                $new_content .= substr($the_content,($epos+1));
                        } else {
                                // must have 2 arguments
                                throw new Exception('etsy-include: missing arguments');
                        }

                        // other bracket to parse?
                        if ($epos+1 < strlen($the_content)) {
                                $new_content = etsy_shop_post($new_content);
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

function etsy_shop_findAllShopSectionListings($etsy_shop_id, $etsy_section_id) {
        $etsy_cache_file = dirname(__FILE__).'/tmp/etsy'.$etsy_section_id.'_cache_'.$parsed['host'].'.json';
    
        // if no cache file exist
        if (!file_exists($etsy_cache_file) or (time() - filemtime($etsy_cache_file) >= ETSY_SHOP_CACHE_LIFE)) {
        $reponse = etsy_shop_api_request("shops/$etsy_shop_id/sections/$etsy_section_id/listings");
        $tmp_file = $etsy_cache_file.rand().'.tmp';

        file_put_contents($tmp_file, $reponse);
        rename($tmp_file, $etsy_cache_file);
    } else {
        // read cache file
        $reponse = file_get_contents($etsy_cache_file);
    }
    
    $data = json_decode($reponse);
    return $data;
}

function etsy_shop_getListing($etsy_listing_id) {
    $parsed = parse_url(get_bloginfo('home'));
    $etsy_cache_file = sys_get_temp_dir().'/etsy'.$etsy_listing_id.'_cache_'.$parsed['host'].'.json';
    
    // if no cache file exist
    if (!file_exists($etsy_cache_file) or (time() - filemtime($etsy_cache_file) >= ETSY_SHOP_CACHE_LIFE)) {
        $reponse = etsy_shop_api_request("/listings/$etsy_listing_id");
        $tmp_file = $etsy_cache_file.rand().'.tmp';

        file_put_contents($tmp_file, $response);
        rename($tmp_file, $etsy_cache_file);
    } else {
        // read cache file
        $reponse = file_get_contents($etsy_cache_file);
    }
    
    $data = json_decode($reponse);
    return $data->results[0];
}

function etsy_shop_findAllListingImages($etsy_listing_id) {
    $etsy_cache_file = dirname(__FILE__).'/tmp/etsy'.$etsy_listing_id.'_cache_'.$parsed['host'].'.json';
    
    // if no cache file exist
    if (!file_exists($etsy_cache_file) or (time() - filemtime($etsy_cache_file) >= ETSY_CACHE_LIFE)) {
        $reponse = etsy_shop_api_request("/listings/$etsy_listing_id/images");
        $tmp_file = $etsy_cache_file.rand().'.tmp';

        file_put_contents($tmp_file, $reponse);
        rename($tmp_file, $etsy_cache_file);
    } else {
        // read cache file
        $reponse = file_get_contents($etsy_cache_file);
    }
    
    $data = json_decode($reponse);
    return $data->results[0]->url_170x135;
}

function etsy_shop_api_request($etsy_request) {
    $etsy_api_key = get_option('etsy_shop_api_key');
    $url = "http://openapi.etsy.com/v2/$etsy_request?api_key=" . $etsy_api_key;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response_body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (intval($status) != 200) throw new Exception("HTTP $status\n$response_body");
    
    return $response_body;
}

function etsy_shop_generate_listing($listing_id, $title, $state, $price, $currency_code, $quantity, $url) {
    if (strlen($title) > 18) {
        $title = substr($title, 0, 25);
        $title .= "...";
    }
    
    // if the Shop Item is active
    if ($state == 'active') {
        // todo translation
        $state = 'Disponible';
        
        $script_tags =  '
            <div class="listing-card" id="' . $etsy_listing_id . '">
                <a title="' . $title . '" href="' . $url . '" class="listing-thumb">
                    <img alt="' . $title . '" src="' . etsy_shop_findAllListingImages($listing_id) . '">          
                </a>
                <div class="listing-detail">
                    <p class="listing-title">
                        <a title="' . $title . '" href="' . $url . '">'.$title.'</a>
                    </p>
                    <p class="listing-maker">
                        <a title="' . $title . '" href="' . $url . '">'.$state.'</a>
                    </p>
                </div>
                <p class="listing-price">$'.$price.' <span class="currency-code">'.$currency_code.'</span></p>
            </div>'; 
            
        return $script_tags;
    } else {
        return '';
    }
}

// Custom CSS

add_action('wp_print_styles', 'etsy_shop_css');

function etsy_shop_css() {
  $link = plugins_url( 'etsy-shop.css', __FILE__ );
  wp_register_style('etsy_shop_style', $link);
  wp_enqueue_style('etsy_shop_style');
}


// Options Menu
add_action('admin_menu', 'etsy_shop_menu');

function etsy_shop_menu() {
        add_options_page(__('Etsy Shop Options', 'etsyshop'), __('Etsy Shop', 'etsyshop'), 'manage_options', basename(__FILE__), 'etsy_shop_options_page');
}

function etsy_shop_options_page() {
        // did the user is allowed?
        if (!current_user_can('manage_options'))  {
            wp_die( __('You do not have sufficient permissions to access this page.', 'etsyshop') );
        }

        // did the user enter an API Key?
        if (isset($_POST['etsy_shop_api_key'])) {
                $etsy_shop_api_key = $_POST['etsy_shop_api_key'];
                update_option('etsy_shop_api_key', $etsy_shop_api_key);
                // and remember to note the update to user
                $updated = true;
    }
add_action('wp_print_styles', 'etsy_shop_css');
    // grab the Etsy API key
    if(get_option('etsy_shop_api_key')) {
        $etsy_shop_api_key = get_option('etsy_shop_api_key');
    } else {
        add_option('etsy_shop_api_key', '');
    }

    if ($updated) {
        echo '<div class="updated fade"><p><strong>'. __('Options saved.', 'etsyshop') .'</strong></p></div>';
    }

    // print the Options Page
    ?>
    
        <div id="icon-options-general" class="icon32"><br /></div><h2><?php _e('Etsy Shop Options', 'etsyshop'); ?></h2>
        <form name="etsy_shop_options_form" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
                <table class="form-table"> 
                        <tr valign="top"> 
                                <th scope="row"><label for="etsy_shop_api_key"></label><?php _e('Etsy API Key', 'etsyshop'); ?></th> 
                                <td><input id="etsy_shop_api_key" name="etsy_shop_api_key" size="25" value="<?php echo get_option('etsy_shop_api_key'); ?>" /></td>
                        </tr> 
        </table>

        <p class="submit">
                <input type="submit" name="submit" id="submit" class="button-primary" value="<?php _e('Save Changes', 'etsyshop'); ?>" />
        </p>
    
        </form>
<?php
}

// Admin Warning
if (is_admin()) {
        etsy_shop_warning();
}

function etsy_shop_warning() {
    if (!get_option('etsy_shop_api_key')) {
        function etsy_shop__api_key_warning() {
            echo "<div id='etsy-warning' class='updated fade'><p><strong>".__('Etsy Shop is almost ready.', 'etsyshop')."</strong> ".sprintf(__('You must <a href="%1$s">enter your Etsy API key</a> for it to work.', 'etsyshop'), "options-general.php?page=etsy-shop.php")."</p></div>";
        }
            
        add_action('admin_notices', 'etsy_shop__api_key_warning');
    }
}

?>
