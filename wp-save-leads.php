<?php
/*
Plugin Name: Contact Form 7 - Save Leads
Description: Save All Contact Form 7 Leads
Author: Bosonet
Author URI: http://www.bosonet.com/
Version: 1.0.2
*/

class BosonetWpcf7SaveLeads {

	public function __construct(){

		add_action('plugins_loaded', array($this ,'save_leads_fields'), 10);
		add_action( 'init', array($this ,'register_cpt_bosoWpcf7Sl' ));
		add_action( 'add_meta_boxes', array($this ,'add_blead_logo_meta_box' ));
		add_action( 'add_meta_boxes', array($this ,'add_blead_meta_box' ));
		add_action( 'add_meta_boxes', array($this ,'add_blead_handled_data_meta_box' ));
		add_action( 'save_post',array($this , 'save_bosoWpcf7Sl_meta' ));
		add_action( 'wpcf7_before_send_mail', array($this ,'bprocess_form' ));
		add_filter('manage_bosoWpcf7Sl_posts_columns', array($this ,'bosoWpcf7Sl_custom_columns_head'));
		add_action( 'manage_bosoWpcf7Sl_posts_custom_column', array($this ,'bosoWpcf7Sl_custom_columns_content'), 10, 2);
		add_action( 'admin_menu', array($this ,'add_bosoWpcf7Sl_menu_bubble') );

		if( is_admin() && isset($_GET['post_type']) && $_GET['post_type'] == 'bosowpcf7sl' ) {
			add_filter('request', array($this ,'_bosoWpcf7Sl_created_in_posts_filter_RequestAdmin'));
			add_filter('restrict_manage_posts', array($this ,'_bosoWpcf7Sl_created_in_posts_filter_RestrictManagePosts'));
			add_filter('request', array($this ,'bosoWpcf7Sl_handled_by_posts_filter_RequestAdmin'));
			add_filter('restrict_manage_posts', array($this ,'bosoWpcf7Sl_handled_by_posts_filter_RestrictManagePosts'));
		}
	}
	// this plugin needs to be initialized AFTER the Contact Form 7 plugin.
	public  function save_leads_fields() {
		global $pagenow;
		if(!function_exists('wpcf7_add_shortcode')) {
			if($pagenow != 'plugins.php') { return; }
			add_action('admin_notices', 'cftagitfieldserror');
			function cftagitfieldserror() {
				$out = '<div class="error update-message" id="messages"><p> ';
				if(file_exists(WP_PLUGIN_DIR.'/contact-form-7/wp-contact-form-7.php')) {

					$out .= 'Please <strong>Activate</strong> "Contact Form 7" plugin to make <strong>"Save Leads"</strong> plugin work!';
				} else {
					$out .= 'The <strong>"Contact Form 7"</strong> plugin must be installed to make <strong>"Save Leads"</strong> plugin work. <a href="'.admin_url('plugin-install.php?tab=plugin-information&plugin=contact-form-7&from=plugins&TB_iframe=true&width=600&height=550').'" class="thickbox" title="Contact Form 7">Install Now.</a>';
				}
				$out .= '</p></div>';
				echo $out;
			}
		}
	}

	public  function register_cpt_bosoWpcf7Sl() {
		$labels = array(
			'name' => _x( 'Save Leads - [Developed by bosonet]', 'bosoWpcf7Sl' ),
			'singular_name' => _x( 'Lead', 'bosoWpcf7Sl' ),
			'edit_item' => _x( 'Edit Lead', 'bosoWpcf7Sl' ),
			'view_item' => _x( 'View Lead', 'bosoWpcf7Sl' ),
			'search_items' => _x( 'Search Leads', 'bosoWpcf7Sl' ),
			'not_found' => _x( 'No leads found', 'bosoWpcf7Sl' ),
			'not_found_in_trash' => _x( 'No leads found in Trash', 'bosoWpcf7Sl' ),
			'parent_item_colon' => _x( 'Parent Lead:', 'bosoWpcf7Sl' ),
			'menu_name' => _x( 'Save Leads', 'bosoWpcf7Sl' ),
		);
		$args = array(
			'labels' => $labels,
			'hierarchical' => true,
			'description' => 'Leads that created by "Contact Form 7" submit',
			'supports' => array( 'title' ),
			'public' => false,
			'show_ui' => true,
			'show_in_menu' => true,
			'menu_position' => 50,
			'menu_icon' => 'dashicons-format-aside',
			'show_in_nav_menus' => true,
			'publicly_queryable' => true,
			'exclude_from_search' => false,
			'has_archive' => true,
			'query_var' => true,
			'can_export' => true,
			'rewrite' => true,
			'capability_type' => 'post',
			'capabilities' => array(
				'create_posts' => false,
			),
			'map_meta_cap' => true,
		);
		register_post_type( 'bosoWpcf7Sl', $args );
	}

	public  function add_blead_logo_meta_box() {
		add_meta_box('blogo_meta_box','Developed by bosonet',array($this,'show_bosoWpcf7Sl_logo_meta_box'),'bosoWpcf7Sl','side','high');
	}

	public  function show_bosoWpcf7Sl_logo_meta_box() {
		echo '<a href="http://www.bosonet.com/" target="_blank"><img src="'.plugin_dir_url(__FILE__) .'bosonet-logo.png" alt="bosonet"></a>';
	}

	public  function add_blead_meta_box() {
		add_meta_box('blead_fields_meta_box','Fields of Lead',array($this,'show_bosoWpcf7Sl_meta_box'),'bosoWpcf7Sl','normal','high');
	}

	public  function show_bosoWpcf7Sl_meta_box() {
		global $post;
		global $current_user;
		$meta = get_post_meta( $post->ID, '', true );
		//print_r($meta);
		echo '<input type="hidden" name="_bosoWpcf7Sl_meta_box_nonce" value="'.wp_create_nonce( basename(__FILE__) ).'">';
		foreach($meta as $key=>$val)
		{
			if(strpos($key, 'bosoWpcf7Sl_') === 0 && $key !='bosoWpcf7Sl_handled_by' && $key !='bosoWpcf7Sl_status' && strpos($key, 'bosoWpcf7Sl_comments_') !== 0){
				echo '<p>'.'<label for="'.$key.'_">'.substr($key,17).'</label><br>'.
				'<textarea name="'.$key.'" id="'.$key.'" rows="1" cols="30" style="width:100%;">'.(is_array($val) ? implode(",", $val) : $val).'</textarea></p>';
			}
		}
	}

	public  function add_blead_handled_data_meta_box() {
		add_meta_box('blead_handled_data_meta_box','Handled Data',array($this,'show_blead_handled_data_meta_box'),'bosoWpcf7Sl','normal','high');
	}

	public function show_blead_handled_data_meta_box() {
		global $post;
		global $current_user;
		$meta = get_post_meta( $post->ID, '', true );
		$leads_status = implode(" ",$meta['bosoWpcf7Sl_status']);
		$leads_handled_by =  implode(" ",$meta['bosoWpcf7Sl_handled_by']);
		echo '<p><table>'.
		'<tr>'.
		'<th><label for="bosoWpcf7Sl_status">Status:</label></th>'.
		'<th style="width: 160px;">'.
		'<select name="bosoWpcf7Sl_status" id="bosoWpcf7Sl_status" style="width: 100%;">'.
		'<option value="not-attempted" '. (( $leads_status == 'not-attempted' ) ? 'selected="selected"' : '') .'>Not Attempted</option>'.
		'<option value="attempted" '. (( $leads_status == 'attempted' ) ? 'selected="selected"' : '') .'>Attempted</option>'.
		'<option value="contacted" '. (( $leads_status == 'contacted' ) ? 'selected="selected"' : '') .'>Contacted</option>'.
		'<option value="new-opportunity" '. (( $leads_status == 'new-opportunity' ) ? 'selected="selected"' : '') .'>New Opportunity</option>'.
		'<option value="additional-contact" '. (( $leads_status == 'additional-contact' ) ? 'selected="selected"' : '') .'>Additional Contact</option>'.
		'<option value="disqualified" '. (( $leads_status == 'disqualified' ) ? 'selected="selected"' : '') .'>Disqualified</option>'.
		'</select>'.
		'</th>'.
		'</tr>'.
		'<tr>'.
		'<th><label for="bosoWpcf7Sl_handled_by">Handled By:</label></th>'.
		'<th style="width: 160px;"><textarea name="bosoWpcf7Sl_handled_by" id="bosoWpcf7Sl_handled_by" rows="1" cols="30" >'.
		(((strcmp($leads_handled_by,"- - -")==0) || (strcmp($leads_handled_by,"")==0)) ? $current_user->user_login : $leads_handled_by) .
		'</textarea></th>'.
		'</tr>'.
		'</table></p>';

		$comments_nun = 0;
		echo '<hr><p><h3 for="bosoWpcf7Slcomments_">Comments</h3></p>';
		foreach($meta as $key=>$val)
		{
			if(strpos($key, 'bosoWpcf7Sl_comments_') !== false){
				echo '<p>'.'<textarea name="'.$key.'" id="'.$key.'" rows="1" cols="30" style="width:100%;" readonly>'.(is_array($val) ? implode(",", $val) : $val).'</textarea></p>';
				$num = intval(substr($key,21));
				if($num > $comments_nun)
					$comments_nun = $num;
			}
		}
		echo '<div id="new_bcomments" data-comments-bnumber="'.$comments_nun.'">';
		echo '</div>';
		echo '<div id="add_bcomment_button"><input type="button" value="Add Comment" onclick="add_bcomment()" ></div>';
		echo '
		<script>
		function add_bcomment() {
			var div = document.getElementById("new_bcomments");
			var num = parseInt(div.getAttribute("data-comments-bnumber")) +1;
			div.setAttribute("data-comments-bnumber" , num ) ;

			div.innerHTML = div.innerHTML + `<p><textarea name="bosoWpcf7Sl_new_bosoWpcf7Sl_comments_`+num+`" id="bosoWpcf7Sl_new_bosoWpcf7Sl_comments_`+num+`" rows="3" cols="30" style="width:100%;"></textarea></p>`;

			document.getElementById("bosoWpcf7Sl_new_bosoWpcf7Sl_comments_"+num).addEventListener("keydown", bautosizetextarea);

			var btns = document.getElementById("add_bcomment_button");
			btns.removeChild(btns.firstElementChild)

			var btnSave =document.getElementById("publish").cloneNode(true)
			btns.append(btnSave)
		}
		function bautosizetextarea(){
			var el = this;
			setTimeout(function(){
				el.style.cssText = "height:auto; padding:0";
				// for box-sizing other than "content-box" use:
					// el.style.cssText = "-moz-box-sizing:content-box";
					el.style.cssText = "height:" + (el.scrollHeight+8) + "px; width:100%;" ;
				},0);
			}
			var elements = document.getElementsByTagName("textarea");
			for (var i = 0; i < elements.length; i++) {
				elements[i].style.cssText = "height:auto; padding:0";
				elements[i].style.cssText = "height:" + (elements[i].scrollHeight+8) + "px; width:100%;" ;
				elements[i].addEventListener("keydown", bautosizetextarea);
			}
			</script>
			<style>
			input.readonly::first-line, input[readonly]::first-line, textarea.readonly::first-line, textarea[readonly]::first-line {
				color: #0e97ff;
			}
			</style>';
	}

	public function save_bosoWpcf7Sl_meta( $post_id ) {
			if (!isset($_POST['_bosoWpcf7Sl_meta_box_nonce']) || !wp_verify_nonce( $_POST['_bosoWpcf7Sl_meta_box_nonce'], basename(__FILE__) ) ) {
				return $post_id;
			}
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return $post_id;
			}
			if ( 'page' === $_POST['post_type'] ) {
				if ( !current_user_can( 'edit_page', $post_id ) ) {
					return $post_id;
				} elseif ( !current_user_can( 'edit_post', $post_id ) ) {
					return $post_id;
				}
			}
			global $current_user;

			foreach($_POST as $key => $value) {
				if (strpos($key, 'bosoWpcf7Sl_') === 0) {
					$old = get_post_meta( $post_id, $key, true );
					$new = $value;
					if ( $new && $new !== $old ) {
						if (strpos($key, 'bosoWpcf7Sl_comments_') !== false) {
							$new = $current_user->user_login. current_time('  -  d/m/Y  H:i:s' ). " &#13;&#10;".$new;
							$key = substr($key,16);
							update_post_meta( $post_id, $key, $new );
						}
						else
							update_post_meta( $post_id, $key, $new );
					} elseif ( '' === $new && $old ) {
						delete_post_meta( $post_id, $key, $old );
					}
				}
			}
		}

	public  function bprocess_form( $cf7 ) {
			$submission = WPCF7_Submission::get_instance();
			if ( $submission ) {
				$posted_data = $submission->get_posted_data();
				//print_r($posted_data);
				$url = $submission->get_meta( 'url' );
				$postid = url_to_postid( $url );
				$post_id = wp_insert_post(
					array(
						'comment_status'  => 'closed',
						'ping_status'   => 'closed',
						'post_status'   => 'publish',
						'post_title'   => 'lead from: ' ." " . $posted_data['your-name'] ,
						'post_type'   => 'bosoWpcf7Sl'
					)
				);
				add_post_meta($post_id, '_bosoWpcf7Sl_created_by_cf7_id', $cf7->id() , true);
				add_post_meta($post_id, '_bosoWpcf7Sl_created_in_post_id', $postid , true);
				add_post_meta($post_id, 'bosoWpcf7Sl_handled_by', "- - -", true);
				add_post_meta($post_id, 'bosoWpcf7Sl_status', "not-attempted", true);
				foreach($posted_data as $key=>$val)
				{
					if($key[0]!='_'){
						if(is_array($val) )
						$val =implode(" , ", barray_flatten($val));
						add_post_meta($post_id, 'bosoWpcf7Sl_'.$key, $val, true);
					}else{
						add_post_meta($post_id, $key, $val, true);
					}
				}
			}
		}

	public function bosoWpcf7Sl_custom_columns_head($defaults) {
			$defaults['bcf7id'] = 'Created By Contact Form 7';
			$defaults['bpostid'] = 'Created By Post';
			$defaults['bhandledby'] = 'Handled By?';
			return $defaults;
		}

	public  function bosoWpcf7Sl_custom_columns_content($column_name, $post_ID) {
			global $post;
			switch ( $column_name ) {
				case 'bcf7id':
				$mPostID = get_post_meta($post_ID, "_bosoWpcf7Sl_created_by_cf7_id", true);
				echo '<input type="text" onfocus="this.select();" readonly="readonly" value="[contact-form-7 id=&#34;'.$mPostID.'&#34; title=&#34;'.get_the_title($mPostID).'&#34; ]" class="large-text code" style="font-size: 12px;">';
				break;
				case 'bpostid':
				$mPostID = get_post_meta($post_ID, "_bosoWpcf7Sl_created_in_post_id", true);
				echo '<a href="'.get_permalink($mPostID).'">'.get_the_title($mPostID).'</a>';
				break;
				case 'bhandledby':
				echo get_post_meta($post_ID, "bosoWpcf7Sl_handled_by", true);
				break;
			}
		}

	public function add_bosoWpcf7Sl_menu_bubble() {
			global $menu;
			$leads_count = 0;
			$args = array('post_type' => 'bosoWpcf7Sl','posts_per_page' => -1,'meta_query'	=> array('relation'=>'OR',array('key'=>'bosoWpcf7Sl_handled_by','value'=>'- - -','compare'=>'LIKE',),array('key'=>'bosoWpcf7Sl_handled_by','value'=>'','compare'=>'=',),),);
			$the_query = new WP_Query( $args );
			if ( $the_query->have_posts() ) {
				$leads_count = $the_query->post_count;
			}
			wp_reset_postdata();

			foreach ( $menu as $key => $value ) {
				if (strpos($menu[$key][2], "edit.php?post_type=bosowpcf7sl") === 0){
					if ( $leads_count > 0) {
						$menu[$key][0] .= ' <span class="update-plugins"><span class="plugin-count">'.$leads_count.'</span></span>';
					}
					return;
				}
			}
		}

	public  function _bosoWpcf7Sl_created_in_posts_filter_RequestAdmin($request) {
			if( isset($_GET['_bosoWpcf7Sl_created_in_post_id']) && !empty($_GET['_bosoWpcf7Sl_created_in_post_id']) ) {
				$request['meta_key'] = '_bosoWpcf7Sl_created_in_post_id';
				$request['meta_value'] = $_GET['_bosoWpcf7Sl_created_in_post_id'];
			}
			return $request;
		}

	public  function _bosoWpcf7Sl_created_in_posts_filter_RestrictManagePosts() {
			global $wpdb;
			$items = $wpdb->get_col("
			SELECT DISTINCT meta_value
			FROM ". $wpdb->postmeta ."
			WHERE meta_key = '_bosoWpcf7Sl_created_in_post_id'
			");
			?>
			<select name="_bosoWpcf7Sl_created_in_post_id" id="_bosoWpcf7Sl_created_in_post_id">
				<option value="">Created By All Posts</option>
				<?php foreach ($items as $item) { ?>
					<option value="<?php echo esc_attr( $item ); ?>" <?php if(isset($_GET[ '_bosoWpcf7Sl_created_in_post_id']) && !empty($_GET[ '_bosoWpcf7Sl_created_in_post_id']) ) selected($_GET[ '_bosoWpcf7Sl_created_in_post_id'], $item); ?>>
						<?php echo get_the_title(esc_attr($item)); ?>
					</option>
					<?php } ?>
				</select>
				<?php
		}

	public  function bosoWpcf7Sl_handled_by_posts_filter_RequestAdmin($request) {
				if( isset($_GET['bosoWpcf7Sl_handled_by']) && !empty($_GET['bosoWpcf7Sl_handled_by']) ) {
					$request['meta_key'] = 'bosoWpcf7Sl_handled_by';
					$request['meta_value'] = $_GET['bosoWpcf7Sl_handled_by'];
				}
				return $request;
			}

	public  function bosoWpcf7Sl_handled_by_posts_filter_RestrictManagePosts() {
				global $wpdb;
				$items = $wpdb->get_col("
				SELECT DISTINCT meta_value
				FROM ". $wpdb->postmeta ."
				WHERE meta_key = 'bosoWpcf7Sl_handled_by'
				ORDER BY meta_value
				");
				?>
				<select name="bosoWpcf7Sl_handled_by" id="bosoWpcf7Sl_handled_by">
					<option value="">Handled By All</option>
					<?php foreach ($items as $item) { ?>
						<option value="<?php echo esc_attr( $item ); ?>" <?php if(isset($_GET[ 'bosoWpcf7Sl_handled_by']) && !empty($_GET[ 'bosoWpcf7Sl_handled_by']) ) selected($_GET[ 'bosoWpcf7Sl_handled_by'], $item); ?>>
							<?php echo esc_attr($item); ?>
						</option>
						<?php } ?>
					</select>
					<?php
			}

	private function barray_flatten($array) {
					if (!is_array($array)) {
						return false;
					}
					$result = array();
					foreach ($array as $key => $value) {
						if (is_array($value)) {
							$result = array_merge($result, barray_flatten($value));
						} else {
							$result[$key] = $value;
						}
					}
					return $result;
				}

}

$bosonetWpcf7SaveLeads = new BosonetWpcf7SaveLeads();
