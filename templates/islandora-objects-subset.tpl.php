<?php

/**
 * @file
 * Render a bunch of objects in a list or grid view.
 */
?>

<div class="islandora-objects clearfix">
<div class="display-switch-wrapper">
  <span class="islandora-basic-collection-display-switch">
    <?php
    print theme('links', array(
                           'links' => $display_links,
                           'attributes' => array('class' => array('links', 'inline')),
                         )
    );
    ?>
  </span>
</div>
  <?php print $pager; ?>
  <?php print $content; ?>
  <?php print $pager; ?>
</div>
