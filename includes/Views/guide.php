<?php
defined('ABSPATH') || exit;

use Yalogica\NinjaDraw\Models\DataModel;
?>

<div class="ninjadraw-guide">
  <div class="ninjadraw-header">
    <div class="ninjadraw-title">
      <img width="40" src="<?php echo esc_url(NINJADRAW_PLUGIN_URL . 'assets/img/ninjadraw.png'); ?>" />
      <span>NinjaDraw<sup><?php echo sanitize_key(DataModel::getSup()); ?></sup> - </span>
      <span><?php esc_html_e("Guide", "ninjadraw"); ?></span>
    </div>
  </div>
  <div class="ninjadraw-content">
    <h2>How to create a diagram</h2>
    <ol>
      <li>In the WordPress admin sidebar, go to "NinjaDraw" > "All Diagrams"</li>
      <li>Click the "Add New" button.</li>
      <li>Enter a title for your diagram in the title field.</li>
      <li>Draw your diagram on the canvas using shapes, text, arrows, lines, and images from the Media Library.</li>
      <li>Click "Save" to store the diagram and "Publish" to make it available for use. It is now ready to be embedded anywhere on your site.</li>
    </ol>
    
    <h2>Publish with a shortcode</h2>
    <ol>
      <li>On the All Diagrams list, locate the "Shortcode" column and copy the code for your diagram, e.g. [ninjadraw id="123"].</li>
      <li>Paste the shortcode into any post, page, or widget area that supports shortcodes.</li>
    </ol>

    <p>Optional attributes: <code>[ninjadraw id="123" title="true" class="my-diagram"]</code></p>
    
    <ul>
      <li><code>id</code> - the diagram ID (required)</li>
      <li><code>title</code> - set to "true" to display the diagram title</li>
      <li><code>class</code> - a custom CSS class for styling the diagram container</li>
    </ul>

    <h2>Publish with the Gutenberg block</h2>
    <ol>
      <li>Open any post or page in the block editor</li>
      <li>Add a new block and search for "NinjaDraw"</li>
      <li>Select the block, then choose your diagram from the list in the block settings</li>
      <li>In the sidebar toggle "Show title" if needed</li>
      <li>Save or update the post to publish the diagram</li>
    </ol>
  </div>
</div>