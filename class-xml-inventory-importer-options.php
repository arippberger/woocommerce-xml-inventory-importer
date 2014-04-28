<?php
if ( !class_exists( 'XML_Inventory_Importer_Options' ) ) {
    class XML_Inventory_Importer_Options
    {
        /**
         * Holds the values to be used in the fields callbacks
         */
        private $options;

        /**
         * Start up
         */
        public function __construct()
        {
            add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
            add_action( 'admin_init', array( $this, 'page_init' ) );
        }

        /**
         * Add options page
         */
        public function add_plugin_page()
        {
            // This page will be under "Settings"
            add_options_page(
                'Settings Admin', 
                'XML Inventory Import', 
                'manage_options', 
                'xml-inventory-import-setting-admin', 
                array( $this, 'create_admin_page' )
            );
        }

        /**
         * Options page callback
         */
        public function create_admin_page()
        {
            
            // Set class property
            $this->options = get_option( 'xml_inventory_import_option' );

            //var_dump($this->options);

            ?>
            <div class="wrap">
                <?php screen_icon(); ?>
                <h2>XML inventory Importer</h2>           
                <form method="post" action="options.php">
                <?php
                    echo 'test';
                    // This prints out all hidden setting fields
                    settings_fields( 'xml_inventory_import_option_group' );   
                    do_settings_sections( 'xml-inventory-import-setting-admin' );
                    submit_button(); 
                ?>
                </form>
            </div>
            <?php
        }

        /**
         * Register and add settings
         */
        public function page_init()
        {        
            register_setting(
                'xml_inventory_import_option_group', // Option group
                'xml_inventory_import_option', // Option name
                array( $this, 'sanitize' ) // Sanitize
            );

            add_settings_section(
                'xml_inventory_import_settings', // ID
                'XML inventory Import Settings', // Title
                array( $this, 'print_section_info' ), // Callback
                'xml-inventory-import-setting-admin' // Page
            );  

            //add setting for ftp server
            add_settings_field(
                'ftp_server', // ID
                'FTP Server', // Title 
                array( $this, 'ftp_server_callback' ), // Callback
                'xml-inventory-import-setting-admin', // Page
                'xml_inventory_import_settings' // Section           
            );      

            //add setting for ftp user name
            add_settings_field(
                'ftp_user_name', 
                'FTP User Name', 
                array( $this, 'ftp_user_name_callback' ), 
                'xml-inventory-import-setting-admin', 
                'xml_inventory_import_settings'
            );  

            //add setting for ftp user password
            add_settings_field(
                'ftp_user_pass', 
                'FTP User Password', 
                array( $this, 'ftp_user_pass_callback' ), 
                'xml-inventory-import-setting-admin', 
                'xml_inventory_import_settings'
            );  

            //add setting for ftp directory
            add_settings_field(
                'ftp_directory', 
                'FTP Directory', 
                array( $this, 'ftp_directory_callback' ), 
                'xml-inventory-import-setting-admin', 
                'xml_inventory_import_settings'
            );   

            //add setting for xml filename
            add_settings_field(
                'xml_filename', 
                'XML File Name', 
                array( $this, 'xml_filename_callback' ), 
                'xml-inventory-import-setting-admin', 
                'xml_inventory_import_settings'
            );   

            //add setting for xml filename extension
            add_settings_field(
                'xml_filename_extension', 
                'XML Filename Extension', 
                array( $this, 'xml_filename_extension_callback' ), 
                'xml-inventory-import-setting-admin', 
                'xml_inventory_import_settings'
            );               

            //add setting for FTP interval
            add_settings_field(
                'ftp_interval', 
                'FTP Interval Period', 
                array( $this, 'ftp_interval_callback' ), 
                'xml-inventory-import-setting-admin', 
                'xml_inventory_import_settings'
            );                


        }

        /**
         * Sanitize each setting field as needed
         *
         * @param array $input Contains all settings fields as array keys
         */
        public function sanitize( $input )
        {
            $new_input = array();

            if( isset( $input['ftp_server'] ) )
                $new_input['ftp_server'] = sanitize_text_field( $input['ftp_server'] );

            if( isset( $input['ftp_user_name'] ) )
                $new_input['ftp_user_name'] = sanitize_text_field( $input['ftp_user_name'] );

            if( isset( $input['ftp_user_pass'] ) )
                $new_input['ftp_user_pass'] = sanitize_text_field( $input['ftp_user_pass'] );

            if( isset( $input['ftp_directory'] ) )
                $new_input['ftp_directory'] = sanitize_text_field( $input['ftp_directory'] );

            if( isset( $input['xml_filename'] ) ) {
                //remove .xml file extension
                $new_input['xml_filename'] = str_replace('.xml', '', $input['xml_filename']);
                //sanitize
                $new_input['xml_filename'] = sanitize_text_field( $input['xml_filename'] );
            }

            if( isset( $input['xml_filename_extension'] ) )
                $new_input['xml_filename_extension'] = sanitize_text_field( $input['xml_filename_extension'] );            

            if( isset( $input['ftp_interval'] ) )
                $new_input['ftp_interval'] = sanitize_text_field( $input['ftp_interval'] );                                                      

            //var_dump($new_input);
            return $new_input;

        }

        /** 
         * Print the Section text
         */
        public function print_section_info()
        {
            print 'Enter your settings below:';
        }

        /** 
         * Get the settings option array and print one of its values
         */
        public function ftp_server_callback()
        {
            printf(
                '<input type="text" id="ftp_server" name="xml_inventory_import_option[ftp_server]" value="%s" />',
                isset( $this->options['ftp_server'] ) ? esc_attr( $this->options['ftp_server']) : ''
            );
        }

        /** 
         * Get the settings option array and print one of its values
         */
        public function ftp_user_name_callback()
        {
            printf(
                '<input type="text" id="ftp_user_name" name="xml_inventory_import_option[ftp_user_name]" value="%s" />',
                isset( $this->options['ftp_user_name'] ) ? esc_attr( $this->options['ftp_user_name']) : ''
            );
        }

        /** 
         * Get the settings option array and print one of its values
         */
        public function ftp_user_pass_callback()
        {
            printf(
                '<input type="text" id="ftp_user_pass" name="xml_inventory_import_option[ftp_user_pass]" value="%s" />',
                isset( $this->options['ftp_user_pass'] ) ? esc_attr( $this->options['ftp_user_pass']) : ''
            );
        }

        /** 
         * Get the settings option array and print one of its values
         */
        public function ftp_directory_callback()
        {
            printf(
                '<input type="text" id="ftp_directory" name="xml_inventory_import_option[ftp_directory]" value="%s" />',
                isset( $this->options['ftp_directory'] ) ? esc_attr( $this->options['ftp_directory']) : ''
            );
        }  

        /** 
         * Get the settings option array and print one of its values
         */
        public function xml_filename_callback()
        {
            printf(
                '<p>Use &#37;-d-&#37; to include dates (uses php date())</p>
                 <input type="text" id="xml_filename" name="xml_inventory_import_option[xml_filename]" value="%s" />.xml',
                isset( $this->options['xml_filename'] ) ? esc_attr( $this->options['xml_filename']) : ''
            );
        }   

        public function xml_filename_extension_callback()
        {
            printf(
                '<p>add filename extension here: either .XML or .xml</p>
                 <input type="text" id="xml_filename_extension" name="xml_inventory_import_option[xml_filename_extension]" value="%s" />',
                isset( $this->options['xml_filename_extension'] ) ? esc_attr( $this->options['xml_filename_extension']) : ''
            );
        }         
        
        /** 
         * Get the settings option array and print one of its values
         */
        public function ftp_interval_callback()
        {?>
            <select name="xml_inventory_import_option[ftp_interval]" id="ftp_interval">
                <option value="everymin" <?php if ($this->options['ftp_interval'] == 'everymin') { echo 'selected'; } ?>>Every Minute</option>
                <option value="every5min" <?php if ($this->options['ftp_interval'] == 'every5min') { echo 'selected'; } ?>>Every Five Minutes</option>                  
                <option value="hourly" <?php if ($this->options['ftp_interval'] == 'hourly') { echo 'selected'; } ?>>Hourly</option> 
                <option value="twicedaily" <?php if ($this->options['ftp_interval'] == 'twicedaily') { echo 'selected'; } ?>>Twice Daily</option>
                <option value="daily" <?php if ($this->options['ftp_interval'] == 'daily') { echo 'selected'; } ?>>Daily</option>
            </select><?php
        }          

    }
}