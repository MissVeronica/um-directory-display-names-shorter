<?php
/**
 * Plugin Name:         Ultimate Member - Directory Display Names Shorter
 * Description:         Extension to Ultimate Member for truncating long display names in Members Directory with addtion of a suffix of three dots.
 * Version:             1.0.0
 * Requires PHP:        7.4
 * Author:              Miss Veronica
 * License:             GPL v3 or later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI:          https://github.com/MissVeronica
 * Plugin URI:          https://github.com/MissVeronica/um-directory-display-names-shorter
 * Update URI:          https://github.com/MissVeronica/um-directory-display-names-shorter
 * Text Domain:         ultimate-member
 * Domain Path:         /languages
 * UM version:          2.8.6
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'UM' ) ) return;

class UM_Directory_Display_Names_Shorter {

    public $directories = array();

    function __construct() {

        define( 'Plugin_Basename_shorter_display_names', plugin_basename( __FILE__ ));

        add_filter( 'um_settings_structure',    array( $this, 'um_settings_structure_shorter_display_names' ), 10, 1 );
        add_filter( 'um_ajax_get_members_data', array( $this, 'um_ajax_get_members_data_truncate_display_name' ), 10, 3 );

        add_filter( 'plugin_action_links_' . Plugin_Basename_shorter_display_names, array( $this, 'shorter_display_names_settings_link' ), 10 );
    }

    public function um_ajax_get_members_data_truncate_display_name( $data_array, $user_id, $directory_data ) {

        if ( $this->valid_form_shorter_display_names( $directory_data )) {

            if ( isset( $data_array['display_name_html'] )) {

                $limit = UM()->options()->get( 'um_directory_display_names_shorter_limit' );
                if ( empty( $limit ) || ! is_numeric( $limit )) {
                    $limit = 5;
                }

                $limit = absint( $limit );

                if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' )) {

                    if ( mb_strlen( $data_array['display_name_html'] ) > $limit ) {
                        $data_array['display_name_html'] = esc_attr( rtrim( mb_substr( $data_array['display_name_html'], 0, $limit )) . '...' );
                    }

                } else {

                    if ( strlen( $data_array['display_name_html'] ) > $limit ) {
                        $data_array['display_name_html'] = esc_attr( rtrim( substr( $data_array['display_name_html'], 0, $limit )) . '...' );
                    }
                }
            }
        }

        return $data_array;
    }

    public function valid_form_shorter_display_names( $directory_data ) {

        $directory_forms = UM()->options()->get( 'um_directory_display_names_shorter_forms' );

        if ( ! empty( $directory_forms )) {

            if ( is_array( $directory_forms ) && isset( $directory_data['form_id'] )) {

                if ( ! in_array( $directory_data['form_id'], $directory_forms )) {
                    return false;
                }
            }
        }

        return true;
    }

    function shorter_display_names_settings_link( $links ) {

        $url = get_admin_url() . 'admin.php?page=um_options&section=users';
        $links[] = '<a href="' . esc_url( $url ) . '">' . __( 'Settings' ) . '</a>';

        return $links;
    }

    public function member_directories() {

        $um_directory_forms = get_posts( array( 'meta_key'    => '_um_mode',
                                                'numberposts' => -1,
                                                'post_type'   => 'um_directory',
                                                'post_status' => 'publish'
                                            ));

        foreach( $um_directory_forms as $um_form ) {
            $this->directories[$um_form->ID] = esc_attr( $um_form->post_title );
        }

        asort( $this->directories );
    }

    public function um_settings_structure_shorter_display_names( $settings_structure ) {

        if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'um_options' ) {
            if ( isset( $_REQUEST['section'] ) && $_REQUEST['section'] == 'users' ) {

                if ( ! isset( $settings_structure['']['sections']['users']['form_sections']['directory_shorter_display_names']['fields'] )) {

                    $plugin_data = get_plugin_data( __FILE__ );

                    $link = sprintf( '<a href="%s" target="_blank" title="%s">%s</a>',
                                                esc_url( $plugin_data['PluginURI'] ),
                                                __( 'GitHub plugin documentation and download', 'ultimate-member' ),
                                                __( 'Plugin', 'ultimate-member' )
                                    );

                    $header = array(
                                        'title'       => __( 'Directory Shorter Display Names', 'ultimate-member' ),
                                        'description' => sprintf( __( '%s version %s - tested with UM 2.8.6', 'ultimate-member' ),
                                                                            $link, esc_attr( $plugin_data['Version'] )),
                                    );

                    $this->member_directories();

                    $section_fields = array();
                    $prefix = '&nbsp; * &nbsp;';

                    $section_fields[] = array(
                        'id'             => 'um_directory_display_names_shorter_forms',
                        'type'           => 'select',
                        'multi'          => true,
                        'size'           => 'medium',
                        'options'        => $this->directories,
                        'label'          => $prefix . __( 'Directories to show shorter display names', 'ultimate-member' ),
                        'description'    => __( 'Select single or multiple Member Directories. None selected equals all selected.', 'ultimate-member' ),
                    );

                    $section_fields[] = array(
                        'id'              => 'um_directory_display_names_shorter_limit',
                        'type'            => 'text',
                        'size'            => 'short',
                        'label'           => $prefix . __( 'Max number of characters in the display name', 'ultimate-member' ),
                        'description'     => __( 'Enter the limit of number of characters to show in the User\'s display name and terminating the name with a suffix of three dots.', 'ultimate-member' ),
                    );

                    $settings_structure['']['sections']['users']['form_sections']['directory_shorter_display_names'] = $header;
                    $settings_structure['']['sections']['users']['form_sections']['directory_shorter_display_names']['fields'] = $section_fields;
                }
            }
        }

        return $settings_structure;
    }
}


new UM_Directory_Display_Names_Shorter();

