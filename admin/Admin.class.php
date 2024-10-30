<?php
/**
 * @package Admin
 */

if ( ! class_exists( 'MCM2I_Admin' ) ) {
	/**
	 * Class that holds most of the admin functionality for WP SEO.
	 */
	class MCM2I_Admin {

		/**
		 * Class constructor
		 */
		function __construct() {
			add_action( 'admin_menu', array( $this, 'register_settings_page' ), 5 );
			add_action( 'wp_ajax_mcm2i_do_ajax', array($this, 'ajax_callbacks' ));

			add_filter( 'plugin_action_links_' . MCM2I_BASENAME, array( $this, 'add_action_link' ), 10, 2 );

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}

		/**
		 * Register the menu item
		 *
		 * @global array $submenu used to change the label on the first item.
		 */
		function register_settings_page() {

			// add_management_page( $page_title, $menu_title, $capability, $menu_slug, $function );
			add_management_page( 'Minecraft Map to Image', 'Minecraft<br />Map to Image', 'upload_files', 'minecraft-map-to-image', array( $this, 'load_page' ) );

		}

		function ajax_callbacks() {

	       	// ************************
        	// check for uploaded map
	        foreach ($_FILES as $key => $value)
	        {
	            //GET FILE CONTENT
	            $tmp_file_path =  $value['tmp_name'];
	        }

	        if('application/octet-stream' != $value['type']) {
	        	$output['status'] = 'error';  
	        	$output['error_message'] = 'Not a map (incorrect file type)';
	        	print_r(json_encode($output)); die();
	        }


	        if(10000 < $value['size']) {
	        	$output['status'] = 'error';  
	        	$output['error_message'] = 'Not a map (filesize too large)';
	        	print_r(json_encode($output)); die();
	        }

	       	// ************************
        	// convert map	        

			$mcMap = new MCM2I_McMap();
			$mcMap->load($tmp_file_path);

			// ************************
	        // load gif output to variable
	        ob_start();
	        	imagegif($mcMap->getImage());
	       		$image_gif_string = ob_get_contents();
	        ob_end_clean();

	        // ************************
		    // switch between functions
		    switch($_REQUEST['fn']){
		        case 'mcm2i_convert':

					$output['map_image'] = (base64_encode($image_gif_string));
					$output['map_dimension'] = $mcMap->getDimensionName();
					$output['map_scale'] = $mcMap->getScale();
					$output['map_xcenter'] = $mcMap->getxCenter();
					$output['map_ycenter'] = $mcMap->getyCenter();
					$output['map_name'] = basename($value['name'], ".dat"); //;

					$output['status'] = 'succes';
		            break;

		        case 'mcm2i_save_image':

		        	// *************************
		        	// check for file_name & dimensions)
					$map_save_name = preg_replace("([^\w\s\d\-_~,;:\[\]\(\].]|[\.]{2,})", '', $_REQUEST['map_save_name']);

					$map_save_dimensions = (int)$_REQUEST['map_save_dimensions'];
					if (!in_array($map_save_dimensions, array(1024, 512, 256, 128))) {
					    $map_save_dimensions = 128;
					}

					if($map_save_dimensions != 128)
					{

						// create  new blank image
		    			$destination_image = imagecreate($map_save_dimensions, $map_save_dimensions);
		    			imagetruecolortopalette($destination_image, false, 255);

				    	// load image
				    	$source_image = $mcMap->getImage();

				    	// always copy full source image
				    	$source_x = 0;
				    	$source_y = 0;
				    	$source_width = 128;
				    	$source_height = 128;

				    	$destination_x = 0;
				    	$destination_y = 0;
				    	$destination_width = $map_save_dimensions;
				    	$destination_height = $map_save_dimensions;

						imagecopyresized (
							$destination_image,
							$source_image,
							$destination_x,
							$destination_y,
							$source_x,
							$source_y,
							$destination_width,
							$destination_height,
							$source_width,
							$source_height
						);

						// ************************
				        // load gif output to variable
				        ob_start();
				        	imagegif($destination_image);
				       		$image_gif_string = ob_get_contents();
				        ob_end_clean();

					}

		        	// ************************
		        	// save image upload directory
    				$saved_info = wp_upload_bits($map_save_name . '.gif', null, $image_gif_string);

		        	// *******************
		        	// insert image in Media Library

					// $filename should be the path to a file in the upload directory.
					$filename = $saved_info['file'];

					// Check the type of tile. We'll use this as the 'post_mime_type'.
					$filetype = wp_check_filetype( basename( $filename ), null );

					// Get the path to the upload directory.
					$wp_upload_dir = wp_upload_dir();

					// Prepare an array of post data for the attachment.
					$attachment = array(
						'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ), 
						'post_mime_type' => $filetype['type'],
						'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
						'post_content'   => '',
						'post_status'    => 'inherit'
					);

					// Insert the attachment.
					$attach_id = wp_insert_attachment( $attachment, $filename);

					// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
					require_once( ABSPATH . 'wp-admin/includes/image.php' );

					// Generate the metadata for the attachment, and update the database record.
					$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
					wp_update_attachment_metadata( $attach_id, $attach_data );

		        	// *************
		        	// return status

		        	$output = $saved_info;
		        	$output['attach_id'] = $attach_id;
		        	$output['edit_url'] =  get_edit_post_link($attach_id); 
					$output['status'] = 'succes';
		        	break;

		        default:
		        	$output['status'] = 'error';
		            $output['error_message'] = 'No function specified, check your jQuery.ajax() call';
		            break;
		    }

		    


		    $output = json_encode($output);
		    if(is_array($output)) {
		        print_r($output);  
		    }
		    else {
		        echo $output;
		    }
		    die; // die after ajax request completed
		}

		function load_page() {
		?>
			<div class="wrap">
				<h2>Minecraft Map to Image</h2>
				<div id="mcm2i">
					<div id="mcm2i-container">
						<div id="mcm2i_updated" class="updated" >
							<p></p>
						</div>
						<div id="mcm2i_error" class="error">
							<p></p>
						</div>

						<hr />
						<h3>Upload Minecraft map</h3>

						<form action="" method="post" enctype="multipart/form-data" accept-charset="<?php echo esc_attr( get_bloginfo( 'charset' ) ); ?>">
							<p class="description">
								Minecraft maps are stored as <strong>.dat</strong> files in the <code>/saves/[your_world]/data/</code> folder of your Minecraft directory.<br />
								The files are named like <strong>map_01.dat, map_02.dat etc</strong>.<br />
								<a target="_blank" href="http://minecraftgooglemaps.net/faq/?utm_source=more-information-link&utm_medium=plugin&utm_campaign=minecraft-map-to-image">
									More information about map files
								</a>
							</p>
							<p>Select a Minecraft map file to upload: <input type="file" id="minecraft_map_file" name="minecraft_map_file"/></p>

							<input type="hidden" name="action" value="wp_handle_upload"/>
						</form>


						<hr />
						<h3>Preview image</h3>
						<table class="form-table">
							<tr>
								<th scope="row">
									<div id="mcm2i-image-holder"><p>No map loaded</p></div>
								</th>
								<td id="mcm2i-metadata"></td>
							</tr>
						</table>

						<div class="clear"></div>

						<hr />
						<form id="mcm2i_save_form" action="" method="post" enctype="multipart/form-data" accept-charset="<?php echo esc_attr( get_bloginfo( 'charset' ) ); ?>">
							<h3>Save image</h3>
							<table class="form-table">
								<tr>
									<th scope="row"><label for="map_save_name">Filename</label></th>
									<td><input type="text" id="map_save_name" value="map_save_name" class="regular-text" />.gif</td>
								</tr>
								<tr>
									<th scope="row">Dimensions</th>
									<td>
										<fieldset><legend class="screen-reader-text"><span>Date Format</span></legend>
										<label title='1024x1024'><input type='radio' name='mcm2i_dimensions' value='1024' /> <span>1024 x 1024</span></label><br />
										<label title='512x512'><input type='radio' name='mcm2i_dimensions' value='512' /> <span>512 x 512</span></label><br />
										<label title='256x256'><input type='radio' name='mcm2i_dimensions' value='256' /> <span>256 x 256</span></label><br />
										<label title='128x128'><input type='radio' name='mcm2i_dimensions' value='128' checked='checked'  /> <span>128 x 128</span></label><br />
										</fieldset>
									</td>
								</tr>
							</table>
							<p class="submit">
								<input type="button" id="btn-save-to-library" class="button button-primary" value="Save to Media Library" />
								<img id="mcm2i-ajax-saving" src="<?php echo MCM2I_URL; ?>images/ajax_loader_gray_32.gif" />
							</p>
						</form>
					</div>
					<div id="mcmc2i-sidebar">
						<a target="_blank" href="http://www.minecraftgooglemaps.net/purchase?ref=minecraftmaptoimage">
							<img src="<?php echo MCM2I_URL; ?>images/minecraftgooglemaps.jpg" />
						</a>
						<p>
							With the <a target="_blank" href="http://www.minecraftgooglemaps.net/purchase?ref=minecraftmaptoimage">Minecraft Google Maps plugin</a> you can upload multiple map files at once and automatically create Google Maps.
						</p>
					</div>

				</div>

			</div>
		<?php
		}

		function add_action_link( $links, $file ) {


			// add link to premium support landing page
			$premium_link = '<a target="_new" href="http://www.minecraftgooglemaps.net/">' . __( 'Minecraft Google Maps', 'minecraft-map-to-image' ) . '</a>';
			array_unshift( $links, $premium_link );

			$settings_link = '<a href="' . esc_url( admin_url( 'tools.php?page=minecraft-map-to-image' ) ) . '">' . __( 'Upload map', 'wordpress-seo' ) . '</a>';
			array_unshift( $links, $settings_link );


			// add link to docs
			// $faq_link = '<a href="https://yoast.com/wordpress/plugins/seo/faq/">' . __( 'Settings', 'minecraft-map-to-image' ) . '</a>';
			// array_unshift( $links, $faq_link );

			return $links;
		}

		function enqueue_scripts() {
			wp_enqueue_style( 'mcm2i-admin-style', plugins_url('css/mcm2i-admin.css', MCM2I_FILE), false, MCM2I_VERSION, 'screen');
			wp_enqueue_script( 'mcm2i-admin-script', plugins_url('js/mcm2i-admin.js', MCM2I_FILE), array( 'jquery' ), MCM2I_VERSION, true );
		}


	}
}