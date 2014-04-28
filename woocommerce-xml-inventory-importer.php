<?php
/**
 * Plugin Name: WooCommerce XML Inventory Importer
 * Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
 * Description: Pulls XML from distribution center's server and updates inventory
 * Version: 1.0
 * Author: Alec Rippberger
 * Author URI: http://alecrippberger.com
 * License: GPL2
 */

if ( !class_exists( 'WC_XML_Inventory_Importer' ) ) {
	class WC_XML_Inventory_Importer
	{
		public function __construct() 
		{

			//add everymin cron schedule
			add_filter( 'cron_schedules', array( $this, 'xml_inventory_add_schedule' ) );			

			//schedule cron
			add_action( 'init', array( $this, 'add_scheduled_import' ) );

			// add main inventory import method to cron action
			add_action( 'wcxmlinventoryimportaction', array( $this, 'wc_xml_inventory_import' ) );

			//get the import interval
			$this->options = get_option( 'xml_inventory_import_option' );
			$this->import_interval = $this->options["ftp_interval"]; 

			//register activation hook - here we'll run the main function
			register_activation_hook( __FILE__, array( $this,'wc_xml_inventory_import_activate' ) );

			//initialize admin
			if ( is_admin() ) {
				$this->admin_includes();
			}
			
		}

		//method for adding the cron job
		public function add_scheduled_import() 
		{
			//update import interval 
			$this->options = get_option( 'xml_inventory_import_option' );
			$this->import_interval = $this->options["ftp_interval"]; 

			if ( ! $this->import_interval ) {
				$this->import_interval == 'hourly';
			}

			$current_interval = wp_get_schedule( 'wcxmlinventoryimportaction' );

			//if not currently scheduled
			if ( ! wp_next_scheduled( 'wcxmlinventoryimportaction' ) ) {

				// Schedule import 
				wp_schedule_event( time(), $this->import_interval, 'wcxmlinventoryimportaction' );

			} elseif ( $current_interval != $this->import_interval ) { //if current interval set in options doesn't match scheduled cron

				//unschedule old cron
				wp_clear_scheduled_hook( 'wcxmlinventoryimportaction' );

				// Schedule import 
				wp_schedule_event( time(), $this->import_interval, 'wcxmlinventoryimportaction' );

			}
		}		

		public function admin_includes() 
		{

			// loads the admin settings page and adds functionality to the order admin
			require_once( 'class-xml-inventory-importer-options.php' );
			$this->admin = new XML_inventory_Importer_Options();

		}		

		public function wc_xml_inventory_import_activate() 
		{	

			$this->wc_xml_inventory_import(); //also run on plugin activation

		}

		public function wc_xml_inventory_import() 
		{
			global $woocommerce;

			//get options
			$this->options = get_option( 'xml_inventory_import_option' );

			//define FTP server
			$ftp_server = $this->options["ftp_server"]; 
			$ftp_user_name = $this->options["ftp_user_name"]; 
			$ftp_user_pass = $this->options["ftp_user_pass"]; 
			$ftp_directory = $this->options["ftp_directory"]; 
			$xml_filename = $this->options["xml_filename"]; 
			$xml_filename_extension = $this->options["xml_filename_extension"];
			
			//get the import interval
			$this->import_interval = $this->options["ftp_interval"]; // 'hourly'

			//change any date references (tags) to today's date via php date() function
			$xml_filename = $this->change_date_tag($xml_filename);

			//append .xml to files
			$xml_filename = $xml_filename . $xml_filename_extension;

			//error_log( $xml_filename );

			//create local file to store XML for manipulation
			$local_file = plugin_dir_path( __FILE__ ) . $xml_filename;

			// set up basic connection
			$conn_id = ftp_connect($ftp_server); 

			// login with username and password
			$login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass); 

			// check connection
			if ((!$conn_id) || (!$login_result)) {
			    error_log("FTP connection has failed !");
			}

			// turn passive mode on
			ftp_pasv($conn_id, true);

			// try to change the directory to somedir
			if ( ftp_chdir($conn_id, $ftp_directory ) ) {
				// all good
			} else { 
			    error_log( "Couldn't change directory to " . $ftp_directory );
			}

			//check to see if XML file exists
			$contents_on_server = ftp_nlist( $conn_id, $path );

			if (in_array( $xml_filename, $contents_on_server ) ) {

				// try to download $xml_filename and save to $local_file
				if ( ftp_get( $conn_id, $local_file, $xml_filename, FTP_BINARY ) ) {

					//$xml_source = str_replace(array("&amp;", "&"), array("&", "&amp;"), file_get_contents( $local_file ) );

					$items = simplexml_load_file( $local_file );

					//error_log( "items: " . print_r( $items ) );

					foreach ( $items as $item ) {

						//error_log( "item: " . print_r( $item ) );

						//get variables from item and clean them up
						$sku = strval( $item->SKU );
						$upc = intval( $item->UPC );
						//$item_description = $item->desc;
						$quantity_available = strval( ltrim( $item->QAVL, '0' ) );
						$quantity_on_hand = intval( ltrim( $item->QOHND, '0' ) );

						// error_log( "sku: " . print_r( $sku, true ) );
						// error_log( "upc: " . print_r( $upc, true ) );
						// error_log( "qavl: " . print_r( $quantity_available, true ) );
						// error_log( "qohnd: " . print_r( $quantity_on_hand, true ) );

						//create product object based on SKU
						$product = $this->get_product_by_sku( $sku );

						//error_log( "product: " . print_r( $product, true ) );

						if ( is_a ( $product , 'WC_Product' ) ) {
							//update product object's stock
							$product->set_stock( $quantity_available );
							//error_log( "product stock: " . print_r( strval ( $product->get_total_stock( ) ), true ) );
							error_log( "product sku: " . print_r( $product->get_sku( ), true ) );
							error_log( "set stock returns: " . print_r( $product->set_stock( $quantity_available ), true ) );

						}



						//set stock status based on quantity available
						// if ($quantity_available == 0) {
						// 	$product->set_stock_status( 'outofstock' );
						// } else {
						// 	$product->set_stock_status( 'instock');
						// }



					}
				} else {
					error_log( "There was a problem<br />");
				}

				//delete the local file
				if ( ! unlink( $local_file ) ) {
	  					error_log( "unable to delete local file" );
				}

			} else {
				//xml file does not exist
				//error_log('XML file ' . $xml_filename . ' does not exist');
			}
			// close the connection
			ftp_close($conn_id);

		}

		//add cron schedule for every minute
		public function xml_inventory_add_schedule($schedules) {
		    // interval in seconds
		    $schedules['everymin'] = array('interval' => 60, 'display' => 'Every Minute');
		    $schedules['every5min'] = array('interval' => 5*60, 'display' => 'Every Five Minutes');
		    return $schedules;
		}	

		

		private function get_product_by_sku( $sku ) {

			global $wpdb;

			$product_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $sku ) );

			$_pf = new WC_Product_Factory();  

			$_product = $_pf->get_product( $product_id );

			// from here $_product will be a fully functional WC Product object, 
			// you can use all functions as listed in their api

			if ( $_product ) {

				//error_log("product id: " . print_r( $_product, true ) );

				//return new WC_Product( $product_id );

				return $_product;

			}

			return null;

		}		

		//change php date() variables to date based on timestamp
		private function change_date_tag($input) 
		{
			$start_tag = '%-';
			$end_tag = '-%';
			//get date in filename
			$date_string = $this->getBetween( $input, $start_tag, $end_tag ); 
			//var_dump($date_string);
			//if there is a date in the filename, turn that string into a numerical date based on current timestamp
			if ( ! empty( $date_string ) ) {
			    $return_date = strval( date( $date_string ) );
			} else {
				$return_date = '';
			}
			//remove item (including tags) from input string
			$input = str_replace($start_tag . $date_string . $end_tag, '', $input);

			//append date string to the rest of the input
			$input = $input . $return_date;		

			return $input;
		}

		//get content between 2 points - used for $this->change_date_tage() method
        private function getBetween($content,$start,$end) 
        {
            $r = explode($start, $content);
            if (isset($r[1])){
                $r = explode($end, $r[1]);
                return $r[0];
            }
            return '';
        }     		
	}
}

global $WC_XML_Inventory_Importer;
$WC_XML_Inventory_Importer = new WC_XML_Inventory_Importer();