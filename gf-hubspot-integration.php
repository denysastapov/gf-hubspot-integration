<?php

/**
 * Plugin Name: Gravity Forms HubSpot Integration
 * Plugin URI:  https://github.com/denysastapov
 * Description: Integrates Gravity Forms with HubSpot. Allows you to assign a Portal ID and HubSpot Form ID for each form.
 * Version:     1.2
 * Author:      Denys Astapov
 * Author URI:  https://github.com/denysastapov
 * License:     GPL2+
 */

if (! defined('ABSPATH')) {
  exit;
}

include_once(plugin_dir_path(__FILE__) . 'gf-hubspot-integration-test.php');

class GF_HubSpot_Integration_Plugin
{
  private $option_name = 'gf_hubspot_integrations';

  public function __construct()
  {
    add_action('gform_loaded', array($this, 'init_plugin'));
  }

  public function init_plugin()
  {
    add_action('admin_menu', array($this, 'add_admin_menu'), 20);
    add_action('admin_init', array($this, 'register_settings'));
    add_action('gform_after_submission', array($this, 'handle_form_submission'), 10, 2);
    add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
  }

  public function add_admin_menu()
  {
    add_submenu_page(
      'gf_edit_forms',
      'HubSpot Integration',
      'HubSpot Integration',
      'manage_options',
      'gf-hubspot-integration',
      array($this, 'render_settings_page')
    );
  }

  public function register_settings()
  {
    register_setting('gf_hubspot_integration_group', $this->option_name);
  }

  public function render_settings_page()
  {
    if (! class_exists('GFAPI')) {
      echo '<div class="notice notice-error"><p>Gravity Forms is not installed or activated.</p></div>';
      return;
    }
    $integrations = get_option($this->option_name, array());
    $forms = GFAPI::get_forms();
?>
    <div class="wrap">
      <h1>Gravity Forms HubSpot Integration Settings</h1>
      <p>Specify the Portal ID and HubSpot Form ID for each form to enable integration with HubSpot.</p>
      <form method="post" action="options.php">
        <?php settings_fields('gf_hubspot_integration_group'); ?>
        <table class="wp-list-table widefat fixed striped">
          <thead>
            <tr>
              <th>Form ID</th>
              <th>Form Title</th>
              <th>Portal ID</th>
              <th>HubSpot Form ID</th>
            </tr>
          </thead>
          <tbody>
            <?php if (! empty($forms)) : ?>
              <?php foreach ($forms as $form) :
                $form_id = $form['id'];
                $portalID = isset($integrations[$form_id]['portalID']) ? esc_attr($integrations[$form_id]['portalID']) : '';
                $hubspotFormId = isset($integrations[$form_id]['hubspotFormId']) ? esc_attr($integrations[$form_id]['hubspotFormId']) : '';
              ?>
                <tr>
                  <td><?php echo esc_html($form_id); ?></td>
                  <td><?php echo esc_html($form['title']); ?></td>
                  <td>
                    <input type="text" name="<?php echo $this->option_name; ?>[<?php echo $form_id; ?>][portalID]" value="<?php echo $portalID; ?>" />
                  </td>
                  <td>
                    <input type="text" name="<?php echo $this->option_name; ?>[<?php echo $form_id; ?>][hubspotFormId]" value="<?php echo $hubspotFormId; ?>" />
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else : ?>
              <tr>
                <td colspan="4">No forms were found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
        <?php submit_button(); ?>
      </form>
      <!-- <?php
            gf_hubspot_render_test_section($forms);
            ?> -->
    </div>
<?php
  }

  public function handle_form_submission($entry, $form)
  {
    $integrations = get_option($this->option_name, array());
    $form_id = (string)$form['id'];

    if (empty($integrations[$form_id])) {
      return;
    }

    $portalID = isset($integrations[$form_id]['portalID']) ? $integrations[$form_id]['portalID'] : '';
    $hubspotFormId = isset($integrations[$form_id]['hubspotFormId']) ? $integrations[$form_id]['hubspotFormId'] : '';

    $email_field_id = '';
    $fullname_field_id = '';
    foreach ($form['fields'] as $field) {
      if (! empty($field->adminLabel)) {
        $adminLabel = strtolower(trim($field->adminLabel));
        if ($adminLabel === 'email') {
          $email_field_id = (string)$field->id;
        }
        if ($adminLabel === 'fullname') {
          $fullname_field_id = (string)$field->id;
        }
      }
    }
    $email = $email_field_id ? rgar($entry, $email_field_id) : '';
    $fullname = $fullname_field_id ? rgar($entry, $fullname_field_id) : '';

    $payload = array(
      'fields' => array(
        array('name' => 'fullname', 'value' => $fullname),
        array('name' => 'email', 'value' => $email)
      ),
      'context' => array(
        'pageUri'  => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
        'pageName' => function_exists('get_the_title') ? get_the_title() : ''
      )
    );
    $args = array(
      'headers' => array('Content-Type' => 'application/json'),
      'body'    => json_encode($payload),
      'timeout' => 30,
    );
    $url = "https://api.hsforms.com/submissions/v3/integration/submit/{$portalID}/{$hubspotFormId}";
    wp_remote_post($url, $args);
  }

  public function enqueue_admin_scripts($hook)
  {
    // if ($hook != 'gravityforms_page_gf-hubspot-integration') {
    //   return;
    // }
    wp_enqueue_script('jquery');
    // gf_hubspot_enqueue_test_scripts();
    // error_log("Test scripts enqueued.");
  }
}

new GF_HubSpot_Integration_Plugin();
