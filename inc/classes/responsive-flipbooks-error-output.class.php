<?php

namespace PeakResponsiveFlipbooks;

class ResponsiveFlipbooksErrorOutput {
  public static function printErrorMessage(\WP_Error $errObject, $return = false) {
    ob_start(); ?>
    <div class="error-container">
      <h3 class="error-subject">
        <?php echo $errObject->get_error_code(); ?>
      </h3>
      <p class="error-message">
        <?php echo $errObject->get_error_message(); ?>
      </p>
    </div>
    <?php
    $error = ob_get_clean();
    if ($return)
        return $error;
    else
        print($error);
  }
}
