<?php

namespace PeakResponsiveFlipbooks;

class ResponsiveFlipbooksFlipbookTemplater {

  protected $flipbookID;

  public function __construct($flipbookID) {
    $this->setTemplater('flipbookID', $flipbookID);
  }

  public function getTemplater($templateID) {
    if($this->$templateID) {
      return $this->$templateID;
    }else{
      return false;
    }
  }

  public function setTemplater($templateID, $value) {
    $this->$templateID = $value;
  }

  public function getFlipbookContent($flipbookID) {
    if(empty($flipbookID) || !is_numeric($flipbookID))
      return;

    $bookObj = get_post($flipbookID);
    if(!empty($bookObj)) {
      $bookContent = strip_shortcodes($bookObj->post_content);

      return $bookContent;
    } else{
      $error = new \WP_Error(
        'Nothing to display',
        __('No books found for this id', 'flipbook_scripts')
      );

      return $error;
    }
  }

  /**
   * Get HTML string for flipping.
   * @return string $flipbookMaterial (HTML)
   */
  public function getFlipbookMaterialHTML() {
    $flipbookID = $this->getTemplater('flipbookID');
    $flipbookContent = $this->getFlipbookContent($flipbookID);

    $flipbookMaterial = is_wp_error($flipbookContent) ? ResponsiveFlipbooksErrorOutput::printErrorMessage($flipbookContent, true) : $this->printFlipbookContent($flipbookContent, true);

    return $flipbookMaterial;
  }

  public function printFlipbookContent($flipbookContent, $return = false) {
    $flipbookID = $this->getTemplater('flipbookID');
    ob_start(); ?>
    <div class="flipbook-material">
        <?php
          echo $flipbookContent;
          $this->createFlipbookFromGallery($flipbookID);
        ?>
      </div>
    <?php
    $flipbookContent = ob_get_clean();

    if($return)
      return $flipbookContent;
    else
      print $flipbookContent;
  }

  /**
   * Display post gallery as a flipping book.
   * @param $flipbookID post ID or object.
   */
  public function createFlipbookFromGallery($flipbookID) {
    $post = get_post();
    $pattern = get_shortcode_regex();
    $flids = array();

    if(preg_match_all('/'. $pattern .'/s', $post->post_content, $matches) &&
      in_array("flipbook", $matches[2])) {
      foreach ($matches[2] as $key => $match) {
        if($match == 'flipbook') {
          $shortcode_atts =  explode(" ", $matches[3][$key]);
          if(!empty($shortcode_atts)) {
            foreach ($shortcode_atts as $item_key => $value) {
              if(!empty($value)) {
                $flid =  explode("=",  $value);
                if(in_array($flid[0], array("id", "style", "display"))) {
                  $flids[$key][$flid[0]] = trim($flid[1], '"');
                }
              }
            }
          }
        }
      }
    }

    foreach ($flids as $flid) {
      $atms_ids = get_post_meta($flid["id"], 'ids', true);
      $display = (isset($flid["display"])) ? $flid["display"] : '';
      $style = (isset($flid["style"])) ? $flid["style"] : '';

      $gallery = array();
      if(!empty($atms_ids)) {
        foreach ($atms_ids as $id) {
          $gallery[] = reset(wp_get_attachment_image_src($id, 'large'));
        }
      }

      if(!empty($gallery)): ?>
        <div id="flipbook-<?php echo $flid['id'] ?>"  class="flipping-book <?php echo !empty($display) ? $display : ''; ?> <?php echo !empty($style) ? $style : ''; ?>" >

          <?php $gallery_count = count($gallery);
          $flipbook = "";

          $hard = '';
          foreach( $gallery as $key => $image ) {
            if(empty($style) && $display == 'single' ) {
              $hard =  in_array($key, array(0,$gallery_count-1)) ? 'hard' : '';
            }
            elseif(empty($style)) {
              $hard =  in_array($key, array(0,1,$gallery_count-1)) ? 'hard' : '';
            }
            else {
              $hard = '';
            }
//            if ( $key < 5 ) { // Ahtung! Enable this condition, if you need 5 pages limit (original code)
              $flipbook .= '<div class="page '.$hard.'"><img src="' . $image . '" alt="page content"></div>';
//            }
          }
          echo $flipbook; ?>
          <?php
              wp_localize_script('flipbook_scripts',
                  'bookPages',
                 $gallery
              );
              wp_enqueue_script('flipbook_scripts');
          ?>
        </div><!-- end flip-book -->
      <?php endif;
    }
  }
}
