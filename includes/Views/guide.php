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
    
    <h2>Publish a diagram with a shortcode</h2>
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

    <h2>Publish as a presentation</h2>

    <p>NinjaDraw can display diagram frames as presentation slides. The slide order and transition settings are configured in the Presentation panel of the diagram editor.</p>

    <ol>
      <li>Open a diagram in the NinjaDraw editor.</li>
      <li>Create one or more frames on the canvas. Each frame becomes a presentation slide.</li>
      <li>Open the Presentation panel and arrange the frames in the required order.</li>
      <li>Configure looping, clipping, transition type, and animation effects.</li>
      <li>Save and publish the diagram.</li>
      <li>Open the WordPress post or page where the presentation should appear.</li>
      <li>Add a Shortcode block and paste the presentation shortcode.</li>
      <li>Save or update the page.</li>
    </ol>

    <p>
      Example:
      <code>[ninjadraw id="123" type="presentation" width="100%" height="600px"]</code>
    </p>

    <p>Presentation shortcode attributes:</p>

    <ul>
      <li>
        <code>id</code> — the NinjaDraw diagram ID. This attribute is required.
      </li>
      <li>
        <code>type</code> — set to <code>"presentation"</code> to enable presentation mode.
      </li>
      <li>
        <code>width</code> — presentation container width. The default value is <code>"100%"</code>.
      </li>
      <li>
        <code>height</code> — presentation container height. The default value is <code>"400px"</code>.
      </li>
      <li>
        <code>title</code> — set to <code>"true"</code> to display the diagram title below the presentation.
      </li>
      <li>
        <code>class</code> — an optional custom CSS class added to the presentation container.
      </li>
    </ul>

    <p>
      Supported size units:
      <code>px</code>,
      <code>%</code>,
      <code>rem</code>,
      <code>em</code>,
      <code>vw</code>,
      <code>vh</code>,
      <code>vmin</code>, and
      <code>vmax</code>.
    </p>

    <p>
      Full example:
      <code>[ninjadraw id="123" type="presentation" width="80vw" height="70vh" class="my-presentation"]</code>
    </p>

    <h2>Publish with the Gutenberg block</h2>

    <p>The NinjaDraw Gutenberg block displays regular diagrams. To embed a presentation, use the presentation shortcode shown above.</p>

    <ol>
      <li>Open any post or page in the block editor</li>
      <li>Add a new block and search for "NinjaDraw"</li>
      <li>Select the block, then choose your diagram from the list in the block settings</li>
      <li>In the sidebar toggle "Show title" if needed</li>
      <li>Save or update the post to publish the diagram</li>
    </ol>
  </div>
</div>