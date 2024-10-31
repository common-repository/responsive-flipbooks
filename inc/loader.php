<?php

namespace PeakResponsiveFlipbooks;

class ResponsiveFlipbooksLoader {

  public $pluginDirPath;
  public $pluginDirName;

  public function __construct() {
    $this->setEnvVars();
    $this->loadPluginFiles();
    $this->registerPluginActions();
    $this->registerPluginFilters();
    $this->initFlipbookTemplater();
  }

  function responsive_flipbooks_image_tabs($tabs) {
    $tabs['flipbooks'] = 'Responsive Flipbooks';
    return $tabs;
  }

  function responsive_flipbooks_form() {
    wp_iframe(array($this, 'responsive_flipbooks_form_tab_content'));
  }

  function responsive_flipbooks_form_tab_content() {
    $args = array(
      'post_type' =>'flipbooks',
      'post_status' => 'publish',
      'posts_per_page' => -1,
      'ignore_sticky_posts'=> true
    );
    $fl_query = null;
    $fl_query = new \WP_Query($args);
    $upload_dir = wp_upload_dir();
    ?>
    <ul class="flipbooks-wrapper">
      <?php if( $fl_query->have_posts() ):?>
        <?php while ($fl_query->have_posts()) : $fl_query->the_post(); ?>
            <?php
            $fl_meta_ids = get_post_meta($fl_query->post->ID, 'ids', true);

            if(!empty($fl_meta_ids)) :
              $fl_ids_str = implode(",", $fl_meta_ids);
              ?>
              <li class="flipbook" data-ids="<?php echo $fl_ids_str; ?>" data-id="<?php echo $fl_query->post->ID; ?>">
                <div class="flipbook-image">
                  <?php echo wp_get_attachment_image(reset($fl_meta_ids)); ?>
                </div>
                <div class="flipbook-title">
                  <?php echo $fl_query->post->post_title; ?>
                </div>
              </li>
              <?php
            endif;
            ?>
        <?php endwhile; ?>
      <?php
      endif;
      wp_reset_query();
      ?>
    </ul>
    <?php
  }

  function responsive_flipbooks_admin_js_file() {
    wp_register_script( 'flipbooksAdmin', plugins_url('/assets/js/flipbooks_admin.js', __DIR__), array( 'jquery'), '', true );
    wp_localize_script( 'flipbooksAdmin', 'ajaxParams', array( 'ajaxUrl' => admin_url( 'admin-ajax.php' ),  'nonce' =>  wp_create_nonce( 'flipbooks-nonce' ) ) );
    wp_enqueue_script( 'flipbooksAdmin' );
  }

  function responsive_flipbooks_flipbook_focus_off(){
      $is_focused = get_option('responsive_flipbooks_flipbook_focused');
      update_option( 'responsive_flipbooks_flipbook_focused', false );
      wp_die($is_focused);
  }
  function responsive_flipbooks_flipbook_focus(){
      update_option( 'responsive_flipbooks_flipbook_focus_id', $_POST['id'] );
      update_option( 'responsive_flipbooks_flipbook_focused', true );
      wp_die();
  }
  function responsive_flipbooks_delete(){
      $id = absint($_POST['id']);
      if(!$id) {
        wp_die();
      }
      $post = get_post($id, ARRAY_A);
      if($post['post_type'] == 'flipbooks') {
        wp_delete_post($id);
      }

      wp_die();
  }
  function responsive_flipbooks_update() {
      $id = absint($_POST['id']);
      $ids = array_map('absint', $_POST['ids']);
      $display = sanitize_text_field($_POST['display']);
      $style = sanitize_text_field($_POST['style']);

      $attributes = '';
      if(!$id) {
        wp_die();
      }

      update_post_meta($id, "ids", $ids);
      if(!empty($display) && $display != 'double'){
          update_post_meta($id, "display", $display);
          $attributes = ' display="'.$display.'"';
      }
      if(!empty($style) && $style != 'book'){
          update_post_meta($id, "style", $style);
          $attributes .= ' style="'.$style.'"';
      }

      $title = sanitize_text_field($_POST['title']);

      $flipbook = array();
      $flipbook['ID'] = $id;
      $flipbook['post_title'] = $title;
      $flipbook['post_content'] = '[flipbook id="'.$id.'"'.$attributes.']';

      wp_update_post( $flipbook );
      echo $flipbook['post_content'];
      wp_die();
  }
  function responsive_flipbooks_save() {
    $flipbook_arr = array(
      "post_title" => sanitize_text_field($_POST['title']),
      "post_type" => "flipbooks",
      "post_status" => "publish",
      "post_author" => get_current_user_id(),
    );
    $post_id = wp_insert_post($flipbook_arr);
    if ($post_id && ! is_wp_error($post_id)) {
      add_post_meta($post_id, "ids", $_POST["ids"]);
      if(!empty($_POST["display"]) && !empty($_POST["style"])) {
        $display = sanitize_text_field($_POST["display"]);
        $style = sanitize_text_field($_POST["style"]);
        add_post_meta($post_id, "display", $display);
        add_post_meta($post_id, "style", $style);
        $upd_post_content = array(
          'ID' => $post_id,
          'post_content' => '[flipbook display="'.$display.'" style="'.$style.'" id="'.$post_id.'"]',
        );
      }
      elseif(!empty($_POST["display"])) {
        $display = sanitize_text_field($_POST["display"]);
        add_post_meta($post_id, "display", $display);
        add_post_meta($post_id, "style", "");
        $upd_post_content = array(
          'ID' => $post_id,
          'post_content' => '[flipbook display="'.$display.'" id="'.$post_id.'"]',
        );
      }
      elseif(!empty($_POST["style"])) {
        $style = sanitize_text_field($_POST["style"]);
        add_post_meta($post_id, "display", "");
        add_post_meta($post_id, "style", $style);
        $upd_post_content = array(
          'ID' => $post_id,
          'post_content' => '[flipbook style="'.$style.'" id="'.$post_id.'"]',
        );
      }
      else {
        add_post_meta($post_id, "display", "");
        add_post_meta($post_id, "style", "");
        $upd_post_content = array(
          'ID' => $post_id,
          'post_content' => '[flipbook id="'.$post_id.'"]',
        );
      }
      wp_update_post($upd_post_content);
      echo $post_id;
    }
    wp_die();
  }

  function responsive_flipbooks_media_templates() {
      ?>
    <script type="text/html" id="tmpl-custom-flipbook-setting">
      <h2>Flipbook Settings</h2>
      <label class="setting">
        <span><?php _e('Name'); ?></span>
        <input type="text" data-setting="title" value="{{ typeof flipbook_title !== 'undefined' && flipbook_title !== '(no title)' ? flipbook_title : '' }}">
      </label>
      <label class="setting">
        <span><?php _e('Style'); ?></span>
        <select data-setting="style">
          <option value="book"> <?php echo esc_html__('Book'); ?> </option>
            <# if (typeof flipbook_style !== 'undefined' && flipbook_style === 'magazine') { #>
                <option value="magazine" selected="selected"> <?php echo esc_html__('Magazine'); ?> </option>
            <# } else { #>
                <option value="magazine"> <?php echo esc_html__('Magazine'); ?> </option>
            <# } #>
        </select>
      </label>
      <label class="setting">
        <span><?php _e('Display'); ?></span>
        <select data-setting="display">
          <option value="single"> <?php echo esc_html__('Single'); ?> </option>
            <# if (typeof flipbook_display !== 'undefined' && flipbook_display === 'single') { #>
                <option value="double"> <?php echo esc_html__('Double'); ?> </option>
            <# } else { #>
                <option value="double" selected="selected"> <?php echo esc_html__('Double'); ?> </option>
            <# } #>
        </select>
      </label>
    </script>
    <?php
  }

  function responsive_flipbooks_media_view_strings($strings, $post) {
    $strings = array_merge($strings, array(
      'createFlipbookTitle' => __( 'Create Flipbook' ),
      'createNewFlipbook' => __( 'Create a new flipbook' ),
      'editFlipbookTitle'   => __( 'Edit Flipbook' ),
      'cancelFlipbookTitle' => __( '&#8592; Cancel Flipbook' ),
      'insertFlipbook'      => __( 'Insert flipbook' ),
      'updateFlipbook'      => __( 'Update flipbook' ),
      'addToFlipbook'       => __( 'Add to flipbook' ),
      'addToFlipbookTitle'  => __( 'Add to Flipbook' )
    ));
    return $strings;
  }

  function setEnvVars() {
    $this->pluginDirPath = plugin_dir_path(__DIR__);
    $this->pluginDirName = dirname(__DIR__);
  }

  function getPluginDirPath() {
    return $this->pluginDirPath;
  }

  function getPluginDirName() {
    return $this->pluginDirName;
  }

  public function loadPluginFiles() {
    $this->loadVendorFiles();
    $this->loadPluginIncludes();
  }

  public function loadVendorFiles() {
    $pluginDirPath = $this->getPluginDirPath();
    foreach(glob($pluginDirPath.'inc/classes/*.php') as $class_file) {
      require_once($class_file);
    }
  }

  /**
   * Load plugin shortcodes.
   */
  public function loadPluginIncludes() {
    require_once 'shortcodes.php';
  }

  public function registerPluginActions() {
    add_action('plugins_loaded', array($this, 'registerTextdomain'));
    add_action('wp_enqueue_scripts', array($this, 'loadPluginAssets'));
    add_action('wp_footer', array($this, 'printFlipbookScripts'));

    add_action('media_upload_flipbooks', array($this, 'responsive_flipbooks_form'));
    add_action( 'wp_enqueue_media', array($this, 'responsive_flipbooks_admin_js_file'));
    add_action( 'admin_enqueue_scripts', array($this, 'loadAdminAssets'));
    add_action('admin_enqueue_scripts', function()
    {
      wp_enqueue_media();
    });

    add_action('print_media_templates', array($this, 'responsive_flipbooks_media_templates'));
    add_action('wp_ajax_responsive_flipbooks_save', array($this, 'responsive_flipbooks_save'));
    add_action('wp_ajax_responsive_flipbooks_update', array($this, 'responsive_flipbooks_update'));
    add_action('wp_ajax_responsive_flipbooks_delete', array($this, 'responsive_flipbooks_delete'));
    add_action('wp_ajax_responsive_flipbooks_flipbook_focus', array($this, 'responsive_flipbooks_flipbook_focus'));
    add_action('wp_ajax_responsive_flipbooks_flipbook_focus_off', array($this, 'responsive_flipbooks_flipbook_focus_off'));
  }

  public function registerPluginFilters() {
    add_filter('media_upload_tabs', array($this, 'responsive_flipbooks_image_tabs'), 10, 1);
    add_filter('media_view_strings', array($this, 'responsive_flipbooks_media_view_strings'), 10, 2);
  }

  public function registerTextdomain() {
    load_plugin_textdomain('responsive_flipbooks');
  }

  public function loadPluginAssets() {
    $this->loadFlipbookStylesheets();
    $this->registerFlipbookScripts();
    if(is_singular('flipbooks')) {
      $this->loadFlipbookScripts();
    }
  }

  function loadFlipbookStylesheets() {
    wp_register_style('pluginStyles',
      plugins_url('/assets/css/style.css', __DIR__)
    );

    wp_enqueue_style('pluginStyles');
    wp_enqueue_style( 'wp-mediaelement' );
  }

  function registerFlipbookScripts() {
    wp_register_script('turn_script',
      plugins_url('/assets/js/turn.min.js', __DIR__),
      array('jquery'),
      '0.1'
    );
    wp_register_script('flipbook_scripts',
      plugins_url('/assets/js/flipbook-scripts.js', __DIR__),
      array('jquery', 'turn_script', 'wp-mediaelement')
    );
    wp_localize_script('flipbook_scripts',
      'bookAudioAjax',
      array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'audioNonce' => wp_create_nonce('book-audio-ajax-nonce'),
      )
    );
  }

  function loadFlipbookScripts() {
    wp_enqueue_script('turn_script');
  }

  function printFlipbookScripts() {
    global $load_flipbook_scripts;
    if($load_flipbook_scripts)
      wp_print_scripts(array('flipbook_scripts'));
  }

  function loadAdminAssets() {
    wp_register_style('flipbookAdminStyle',
    plugins_url('/assets/css/style-options.css', __DIR__));

    wp_enqueue_style('flipbookAdminStyle');
    wp_enqueue_script( 'media-script', plugins_url('/assets/js/flipbooks-list.js', __DIR__), array('jquery', 'media-views', 'media-editor' ), '', true);

    $focus_id = get_option('responsive_flipbooks_flipbook_focus_id');
    $post = get_post($focus_id, ARRAY_A);
    $ids = $focus_id ? get_post_meta($focus_id, 'ids', true) : [];
    $title = $focus_id ? esc_html($post['post_title']) : null;
    $display = $focus_id ? get_post_meta($focus_id, 'display', true) : null;
    $style = $focus_id ? get_post_meta($focus_id, 'style', true) : null;

    wp_localize_script( 'media-script', 'flipbook_id', $focus_id);
    wp_localize_script( 'media-script', 'flipbook_data_ids', $ids);
    wp_localize_script( 'media-script', 'flipbook_title', $title);
    wp_localize_script( 'media-script', 'flipbook_display', $display);
    wp_localize_script( 'media-script', 'flipbook_style', $style);
  }

  function initFlipbookTemplater() {
    $pluginDirPath = $this->getPluginDirPath();
    require($pluginDirPath.'inc/responsive-flipbooks-templater.class.php');
  }
}
