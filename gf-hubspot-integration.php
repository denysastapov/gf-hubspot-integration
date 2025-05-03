<?php

declare(strict_types=1);
/**
 * Plugin Name: Gravity Forms HubSpot Integration
 * Plugin URI:  https://github.com/denysastapov
 * Description: Integrates Gravity Forms with HubSpot. Allows assignment of Portal ID and HubSpot Form ID per form.
 * Version:     1.2
 * Author:      Denys Astapov
 * Author URI:  https://github.com/denysastapov
 * License:     GPL2+
 */

if (! defined('ABSPATH')) {
  /**
   * Full path to the WordPress directory.
   * @var string
   */
  define('ABSPATH', dirname(__FILE__, 2) . '/');
}

if (! defined('WPINC')) {
  /**
   * WordPress directory name for includes.
   * @var string
   */
  define('WPINC', 'wp-includes');
}

if (! defined('GFHS_PLUGIN_URL')) {
  define('GFHS_PLUGIN_URL', plugin_dir_url(__FILE__));
}

include_once plugin_dir_path(__FILE__) . 'gf-hubspot-integration-test.php';

class GF_HubSpot_Integration_Plugin
{
  /**
   * Option name for integrations.
   *
   * @var string
   */
  private string $option_name = 'gf_hubspot_integrations';

  public function __construct()
  {
    add_action('gform_loaded', [$this, 'init_plugin']);
  }

  public function init_plugin(): void
  {
    add_action('admin_menu', [$this, 'add_admin_menu'], 20);
    add_action('admin_init', [$this, 'register_settings']);
    add_action('gform_after_submission', [$this, 'handle_form_submission'], 10, 2);
    add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
  }

  public function add_admin_menu(): void
  {
    add_submenu_page(
      'gf_edit_forms',
      'HubSpot Integration',
      'HubSpot Integration',
      'manage_options',
      'gf-hubspot-integration',
      [$this, 'render_settings_page']
    );
  }

  public function register_settings(): void
  {
    register_setting('gf_hubspot_integration_group', $this->option_name);
  }

  public function render_settings_page(): void
  {
    if (! class_exists('GFAPI')) {
      echo '<div class="notice notice-error"><p>Gravity Forms is not installed or activated.</p></div>';
      return;
    }

    $integrations = (array) get_option($this->option_name, []);
    $forms        = GFAPI::get_forms();
?>
    <div class="wrap">
      <h1>Gravity Forms HubSpot Integration Settings</h1>
      <p>Specify the Portal ID and HubSpot Form ID for each form.</p>
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
                $form_id       = (string) $form['id'];
                $portalID      = esc_attr($integrations[$form_id]['portalID'] ?? '');
                $hubspotFormId = esc_attr($integrations[$form_id]['hubspotFormId'] ?? '');
              ?>
                <tr>
                  <td><?php echo esc_html($form_id); ?></td>
                  <td><?php echo esc_html($form['title']); ?></td>
                  <td>
                    <input type="text" name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($form_id); ?>][portalID]" value="<?php echo $portalID; ?>" />
                  </td>
                  <td>
                    <input type="text" name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($form_id); ?>][hubspotFormId]" value="<?php echo $hubspotFormId; ?>" />
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
    </div>
<?php
  }

  public function handle_form_submission(array $entry, array $form): void
  {
    $integrations = (array) get_option($this->option_name, []);
    $form_id      = (string) $form['id'];

    if (empty($integrations[$form_id])) {
      return;
    }

    $portalID      = $integrations[$form_id]['portalID'] ?? '';
    $hubspotFormId = $integrations[$form_id]['hubspotFormId'] ?? '';

    $email_field_id    = '';
    $fullname_field_id = '';
    foreach ($form['fields'] as $field) {
      if (! empty($field->adminLabel)) {
        $label = strtolower(trim((string)$field->adminLabel));
        if ($label === 'email') {
          $email_field_id = (string) $field->id;
        }
        if ($label === 'fullname') {
          $fullname_field_id = (string) $field->id;
        }
      }
    }
    $email    = $email_field_id ? rgar($entry, $email_field_id) : '';
    $fullname = $fullname_field_id ? rgar($entry, $fullname_field_id) : '';

    $payload = [
      'fields'  => [
        ['name' => 'fullname', 'value' => $fullname],
        ['name' => 'email',    'value' => $email],
      ],
      'context' => [
        'pageUri'  => wp_unslash($_SERVER['HTTP_REFERER'] ?? ''),
        'pageName' => function_exists('get_the_title') ? get_the_title() : '',
      ],
    ];

    $args = [
      'headers' => ['Content-Type' => 'application/json'],
      'body'    => wp_json_encode($payload),
      'timeout' => 30,
    ];

    $url = sprintf(
      'https://api.hsforms.com/submissions/v3/integration/submit/%s/%s',
      rawurlencode($portalID),
      rawurlencode($hubspotFormId)
    );

    wp_remote_post($url, $args);
  }

  public function enqueue_admin_scripts(string $hook): void
  {
    wp_enqueue_script('jquery');
  }
}

new GF_HubSpot_Integration_Plugin();
