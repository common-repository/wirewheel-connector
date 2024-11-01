<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once WWC_PLUGIN_DIR . '/includes/WwForm.php';

if (!function_exists('wwc_form_shortcode')) {
    add_shortcode('wwc_form', 'wwc_form_shortcode');
    function wwc_form_shortcode($atts, $content = '')
    {
        extract(
            shortcode_atts(
                array(
                  'id' => false
                ), $atts
            )
        );

        if (!isset($id) || !$id) {
            return;
        }

        $form_data = json_decode(get_option('wwc_form_' . $id));
        $form_id = $form_data->form_id;

        if (null === $form_id || empty($form_id)) {
            return;
        }
        wp_enqueue_script(
            'wirewheel-form-js',
            plugins_url(
                '/js/wirewheel-form.js', __FILE__
            ),
            array('jquery'), '1.0.0', true
        );
        wp_enqueue_style(
            'wirewheen_form_base',
            plugins_url('/css/wirewheel-form-base.css', __FILE__)
        );
        $form = new WWC_Form($form_id);
        $output = $form->buildForm();
        return $output;
    }
}
if (!function_exists('wwc_form_admin_menu')) {
    add_action('admin_menu', 'wwc_form_admin_menu');
    function wwc_form_admin_menu()
    {
        add_menu_page(
            'Wirewheel Forms',
            'Wirewheel Forms',
            'manage_options',
            'wwc_forms',
            'wwc_forms_manage'
        );
        add_submenu_page(
            'wwc_forms',
            'Wirewheel Forms Settings',
            'Settings', 'manage_options',
            'wwc_forms-settings',
            'wwc_forms_settings'
        );
    }
}
/**
 * Settings Form.
 */
if (!function_exists('wwc_forms_settings')) {
    function wwc_forms_settings()
    {
        $ww_existing_config = json_decode(get_option('wwc_form_config'), 1);
        $errors = array();
        $clean = array();

        if (!empty($_POST)
            && wp_verify_nonce(sanitize_key($_POST['ww_nonce']), 'ww_nonce')
        ) {
            $ww_existing_config = [
            'instance_id' => sanitize_text_field(
                $_POST['instance_id']
            ),
            'data_path' => sanitize_text_field(
                $_POST['data_path']
            ),
            'recaptcha_api_information' => sanitize_text_field(
                $_POST['recaptcha_api_information']
            ),
            'data_end_point' => sanitize_text_field(
                $_POST['data_end_point']
            ),
            'default_styling' => sanitize_text_field(
                $_POST['default_styling']
            ),
            'submit_new_request_label' => sanitize_text_field(
                $_POST['submit_new_request_label']
            ),
            ];

            foreach ($_POST as $key => $value) {
                $clean[sanitize_key($key)] = sanitize_text_field($value);
            }

            if (empty($clean['instance_id'])) {
                $errors[] = 'Please enter Instance ID.';
            }

            if (empty($clean['data_path'])) {
                $errors[] = 'Please enter Data Path.';
            }

            if (empty($clean['recaptcha_api_information'])) {
                $errors[] = 'Please enter Recaptcha API Information.';
            }

            if (empty($clean['data_end_point'])) {
                $errors[] = 'Please enter data end point.';
            }

            if (empty($clean['submit_new_request_label'])) {
                $errors[] = 'Please enter the label for Submit new request field.';
            }

            if (count($errors) <= 0) {
                  $config_data = json_encode($clean);
                  update_option('wwc_form_config', $config_data);
                  $success = 'Your settings has been saved successfully.';
                  $clean = array();
            }
        } ?>

  <div id="dashboard-widgets" class="metabox-holder ">
    <style>
      .ww-settings-form .input-text-wrap {
        margin-bottom: 15px;
      }
      .form-element.setting-button {
        margin-top:15px;
      }
    </style>
    <h1><?php esc_html_e('Wirewheel Forms Settings', 'Wirewheel');?></h1>
    <div id="postbox-container-1" class="postbox-container">
      <div class="dashboard-widgets-wrap">
        <div id="dashboard_quick_press" class="ww-settings-form form-wrapper wrap">
          <div class="postbox">
            <div class="inside">
              <?php if (count($errors) > 0) : ?>
                <div class="message error">
                    <?php echo wp_kses(wpautop(implode("\n", $errors)), 'post'); ?>
                </div>
              <?php endif; ?>
              <?php if (isset($success)) : ?>
                <div class="message updated">
                    <?php echo wp_kses(wpautop($success), 'post'); ?>
                </div>
              <?php endif; ?>
              <form method="post" action="" class="initial-form">
                <?php wp_nonce_field('ww_nonce', 'ww_nonce'); ?>
                <div class="input-text-wrap">
                  <label for="instance_id">
                    <strong>
                      <?php esc_html_e('WireWheel Instance ID *', 'Wirewheel');?>
                    </strong>
                  </label>
                  <input type="text" name="instance_id" id="instance_id"
                   size="40" value="<?php
                    if (isset($ww_existing_config['instance_id'])) {
                        echo esc_attr($ww_existing_config['instance_id']);
                    }
                    ?>"/>
                  <span class="description">
                    <?php esc_html_e(
                        'Please enter your WireWheel instance identifier.
                    This will be the subdomain value in the URL. (Ex.
                    https://example.wirewheel.io identifier would be “example”)',
                        'Wirewheel'
                    );?>
                  </span>
                </div>
                <div class="input-text-wrap">
                  <label for="data_path">
                    <strong>
                      <?php esc_html_e('WireWheel Data path *', 'Wirewheel');?>
                    </strong>
                  </label>
                  <br/>
                  <input type="text" name="data_path" id="data_path"
                   size="40" value="<?php
                    if (isset($ww_existing_config['data_path'])) {
                        echo esc_attr($ww_existing_config['data_path']);
                    }
                    ?>"/>
                  <span class="description">
                    <?php esc_html_e(
                        'Please enter your WireWheel data path identifier',
                        'Wirewheel'
                    );?>
                  </span>
                </div>
                <div class="input-text-wrap">
                  <label for="recaptcha_api_information">
                    <strong>
                    <?php $label = 'Recaptcha Sitekey API link *';
                    esc_html_e($label, 'Wirewheel');?>
                    </strong>
                  </label>
                  <br/>
                  <input type="text" name="recaptcha_api_information"
                   id="recaptcha_api_information" size="40" value="<?php
                    if (isset($ww_existing_config['recaptcha_api_information'])) {
                        echo esc_attr(
                            $ww_existing_config['recaptcha_api_information']
                        );
                    }
                    ?>"/>
                  <span class="description">
                    <?php
                    $description = 'Please enter the relative path';
                    $description .= ' of the WireWheel Recaptcha';
                    $description .= ' Sitekey. The path should not start with a "/"';
                    esc_html_e($description, 'Wirewheel');?>
                  </span>
                </div>
                <div class="input-text-wrap">
                  <label for="data_end_point">
                    <strong>
                      <?php esc_html_e('WireWheel Data endpoint *', 'Wirewheel');?>
                    </strong>
                  </label>
                  <br/>
                  <input type="text" name="data_end_point" id="data_end_point"
                   size="40" value="<?php
                    if (isset($ww_existing_config['data_end_point'])) {
                        echo esc_attr($ww_existing_config['data_end_point']);
                    }
                    ?>"/>
                  <span class="description">
                    <?php
                    $description = 'Please enter the relative path';
                    $description .= ' of the WireWheel endpoint.';
                    $description .= ' The path should not start with a "/"';
                    esc_html_e($description, 'Wirewheel');?>
                  </span>
                </div>
                <div class="form-element">
                  <label for="default_styling">
                    <strong>
                      <?php esc_html_e('Use Default Styling', 'Wirewheel');?>
                    </strong>
                  </label>
                  <br/>
                  <input type="checkbox" name="default_styling"
                   id="default_styling" size="40" value="yes" <?php
                    if (isset($ww_existing_config['default_styling'])
                        && $ww_existing_config['default_styling'] == 'yes'
                    ) {
                        echo "checked";
                    }
                    ?>/>
                  <span class="description">
                    <?php
                    $description = 'Uncheck this checkbox ';
                    $description .= 'if you want to use the drfault module styling.';
                    esc_html_e($description, 'Wirewheel');?>
                  </span>
                </div>
                <div class="input-text-wrap">
                  <label for="submit_new_request_label">
                    <strong>
                      <?php esc_html_e('Submit new request label *', 'Wirewheel');?>
                    </strong>
                  </label>
                  <input type="text" name="submit_new_request_label" id="submit_new_request_label"
                   size="40" value="<?php
                    if (isset($ww_existing_config['submit_new_request_label'])) {
                        echo esc_attr($ww_existing_config['submit_new_request_label']);
                    }
                    ?>"/>
                  <span class="description">
                    <?php esc_html_e(
                        'Please enter the label that should be displayed for Submitting a new request.',
                        'Wirewheel'
                    );?>
                  </span>
                </div>
                <div class="form-element setting-button">
                  <input type="submit" name="settingSave"
                   class="button-primary" value="Save"/></div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div id="postbox-container-2" class="postbox-container"></div>
  </div>
    <?php }
}

// Manage Forms.
if (!function_exists('wwc_forms_manage')) {
    function wwc_forms_manage()
    {
        // Sanitize data.
        $ww_nonce = isset($_GET['ww_nonce']) ? sanitize_key($_GET['ww_nonce']) : '';
        $form_id = isset($_GET['del']) ? sanitize_key($_GET['del']) : '';
        $success = '';

        if (isset($_GET['action']) && $_GET['action'] == 'add') {
            return wwc_form_add();
        }
        if (isset($_GET['action']) && $_GET['action'] == 'edit') {
            return wwc_form_edit();
        }

        if ($form_id && wp_verify_nonce($ww_nonce, 'ww_delete')) {
            delete_option('wwc_form_' . $form_id);
            $wwc_form_list = get_option('wwc_form_list');

            if (is_array($wwc_form_list) && in_array($form_id, $wwc_form_list)) {
                $wwc_form_list = array_diff($wwc_form_list, array($form_id));
                update_option('wwc_form_list', $wwc_form_list);
                $success = 'Form ID &quot;' . esc_html($form_id);
                $success .= '&quot; successfully deleted.';
            }
        }

        $wwc_form_list = get_option('wwc_form_list');
        if (!is_array($wwc_form_list)) {
            $wwc_form_list = array();
        }
        ?>

  <div class="wrap">
    <h1><?php esc_html_e('Wirewheel Forms', 'Wirewheel');?></h1>
    <div class="postbox-container">
        <?php if (!empty($success)) {
            esc_html_e($success);
        }
        ?>

      <p>
        <a href="?page=wwc_forms&amp;action=add" class="button-primary">
          <?php esc_html_e('Add New Form +', 'Wirewheel');?>
        </a>
      </p>
          <?php if (count($wwc_form_list) > 0) : ?>
        <table class="wp-list-table widefat fixed striped table-view-list pages">
          <thead>
            <tr>
              <th><?php esc_html_e('Form ID', 'Wirewheel');?></th>
              <th><?php esc_html_e('Shortcode', 'Wirewheel');?></th>
              <th><?php esc_html_e('Actions', 'Wirewheel');?></th>
            </tr>
          </thead>
          <tbody>
                <?php foreach ($wwc_form_list as $wwc_form_id) : ?>
              <tr>
                <td>
                  <strong>
                    <?php echo rawurlencode(esc_html($wwc_form_id)); ?>
                  </strong>
                </td>
                <td>
                  <code>[wwc_form id="<?php echo esc_html($wwc_form_id); ?>"]</code>
                </td>
                <td>
                  <a class="edit" href="?page=wwc_forms&amp;action=edit&amp;id=
                    <?php echo esc_attr($wwc_form_id); ?>">
                    <?php esc_html_e('Edit', 'Wirewheel');?>
                  </a>
                  &nbsp;|&nbsp;
                  <a class="submitdelete" onclick="
                    return confirm(
                      'Are you sure you want to delete this form permanently?'
                    );"
                    href="?page=wwc_forms&amp;ww_nonce=
                    <?php echo esc_attr(wp_create_nonce('ww_delete')); ?>
                    &amp;del=
                    <?php echo esc_attr($wwc_form_id); ?>">
                    <?php esc_html_e('Delete', 'Wirewheel');?>
                  </a>
                </td>
              </tr>
                <?php endforeach; ?>
          </tbody>
        </table>
          <?php endif; ?>
    </div>
  </div>
    <?php }
}

/**
* Form Add Method.
*/
if (!function_exists('wwc_form_add')) {
    function wwc_form_add()
    {
        $wwc_form_list = get_option('wwc_form_list');
        if (!is_array($wwc_form_list)) {
            $wwc_form_list = array();
        }

        $errors = array();
        $clean = array();
        $success = '';

        if (!empty($_POST)
            && wp_verify_nonce(sanitize_key($_POST['ww_nonce']), 'ww_nonce')
        ) {
            foreach ($_POST as $key => $value) {
                $clean[sanitize_key($key)] = stripslashes($value);
            }

            if (empty($clean['form_id'])) {
                $errors[] = 'Please enter a unique Form ID.';
            }
            if (false !== get_option('wwc_form_' . $clean['form_id'])) {
                $err_msg = 'This Form ID already exits,';
                $err_msg .= ' Please enter unique Form ID';
                $errors[] = $err_msg;
            }

            if (count($errors) <= 0) {
                // save snippet
                $form_id = strtolower($clean['form_id']);
                $wwc_form_list[] = $form_id;
                $config_data = json_encode($clean);
                update_option('wwc_form_list', $wwc_form_list);
                update_option('wwc_form_' . $form_id, $config_data);
                $success = 'Your form has been saved successfully.';
                $clean = array();
            }
            if (isset($_POST['addform']) && count($errors) <= 0) { ?>
      <script type="text/javascript">
        window.location = "?page=wwc_forms";
      </script>
            <?php }
        } ?>
  <div class="wrap">
        <?php if (count($errors) > 0) : ?>
          <div class="message error">
            <?php echo wp_kses(wpautop(implode("\n", $errors)), 'post'); ?>
          </div>
        <?php endif; ?>
        <?php if (!empty($success)) : ?>
          <div class="message updated">
            <?php echo wp_kses(wpautop($success), 'post'); ?>
          </div>
          <p>
            <a class="button" href="?page=wwc_forms">&laquo;
              <?php esc_html_e('Back to Form List', 'Wirewheel');?>
            </a> <?php esc_html_e('or', 'Wirewheel');?>
            <a class="button" href="?page=wwc_forms&amp;add=1">
              <?php esc_html_e('Add New Form +', 'Wirewheel');?>
            </a>
          </p>
        <?php else: ?>
      <form method="post" action="">
            <?php wp_nonce_field('ww_nonce', 'ww_nonce'); ?>
        <div class="form-element">
          <label for="form_id"><?php esc_html_e('Form ID:', 'Wirewheel');?></label>
          <br/>
          <input type="text" name="form_id" id="form_id" size="40" value="<?php
            if (isset($clean['form_id'])) {
                echo esc_attr($clean['form_id']);
            }
            ?>"/>
        </div>
        <div class="form-element">
          <label for="show_title">
            <?php esc_html_e('Show Title', 'Wirewheel');?>
          </label>
          <br/>
          <input type="checkbox" name="show_title" id="show_title"
           size="40" value="1" />
          <span class="description">
            <?php
            $description = 'Includes the title';
            $description .= ' of your Privacy Studio Page when rendering your form.';
            esc_html_e($description, 'Wirewheel');?>
          </span>
        </div>
        <div class="form-element">
          <label for="show_description">
            <?php esc_html_e('Show Description', 'Wirewheel');?>
          </label>
          <br/>
          <input type="checkbox" name="show_description" id="show_description"
           size="40" value="1" />
          <span class="description">
            <?php
            $description = 'Includes the description';
            $description .= ' of your Privacy Studio Page when rendering your form.';
            esc_html_e($description, 'Wirewheel');?>
          </span>
        </div>
        <div class="form-element">
          <input type="submit" name="addform" class="button-primary" value="Save"/>
        </div>
      </form>
        <?php endif; // end no success ?>
  </div>
    <?php }
}

/**
 * Form Edit Method.
 */
if (!function_exists('wwc_form_edit')) {
    function wwc_form_edit()
    {
        $errors = array();
        $clean = array();
        $success = '';
        $title_selected = '';
        $desc_selected = '';
        if (isset($_GET['id'])) {
            $form_id = sanitize_key($_GET['id']);
            $form_data = get_option('wwc_form_' . $form_id);
            $form_data = json_decode($form_data);
            $title_selected = isset($form_data->show_title) ? 'checked' : '';
            $desc_selected = isset($form_data->show_description) ? 'checked' : '';
        }

        $wwc_form_list = get_option('wwc_form_list');
        if (!is_array($wwc_form_list)) {
            $wwc_form_list = array();
        }

        if (!empty($_POST)
            && wp_verify_nonce(sanitize_key($_POST['ww_nonce']), 'ww_nonce')
        ) {
            foreach ($_POST as $key => $value) {
                $clean[sanitize_key($key)] = stripslashes($value);
            }

            if (empty($clean['form_id'])) {
                $errors[] = 'Please enter a unique Form ID.';
            }
            if (count($errors) <= 0) {
                $config_data = json_encode($clean);
                $form_id = strtolower($clean['form_id']);
                if (false !== get_option('wwc_form_' . $form_id)) {
                    // Already exists, so update it.
                    update_option('wwc_form_' . $form_id, $config_data);
                } else {
                    $wwc_form_list[] = $form_id;
                    update_option('wwc_form_list', $wwc_form_list);
                    update_option('wwc_form_' . $form_id, $config_data);
                    $success = 'Your form has been saved successfully.';
                    $clean = array();
                }
            }
            if (isset($_POST['editform']) && count($errors) <= 0) { ?>
        <script type="text/javascript"> window.location = "?page=wwc_forms";</script>
            <?php }
        } ?>

    <div class="wrap">
        <?php if (count($errors) > 0) : ?>
        <div class="message error">
            <?php echo wp_kses(wpautop(implode("\n", $errors)), 'post'); ?>
        </div>
      <?php else: ?>
        <form method="post" action="">
          <?php wp_nonce_field('ww_nonce', 'ww_nonce'); ?>
          <div class="form-element">
            <label for="form_id"><?php esc_html_e('Form ID:', 'Wirewheel');?></label>
            <br/>
            <input type="text" name="form_id" id="form_id" size="40" value="<?php
            if (isset($form_id)) {
                echo wp_kses($form_id, 'post');
            }
            ?>"/>
          </div>
          <div class="form-element">
            <label for="show_title">
              <?php esc_html_e('Show Title', 'Wirewheel');?>
            </label>
            <br/>
            <input type="checkbox" name="show_title" id="show_title" size="40"
             value="1" <?php echo wp_kses($title_selected, 'post'); ?> />
            <span class="description">
              <?php esc_html_e('Show Title', 'Wirewheel');?>
            </span>
          </div>
          <div class="form-element">
            <label for="show_description">
              <?php esc_html_e('Show Description', 'Wirewheel');?>
            </label>
            <br/>
            <input type="checkbox" name="show_description" id="show_description"
             size="40" value="1" <?php echo wp_kses($desc_selected, 'post'); ?>/>
            <span class="description">
              <?php
                $description = 'Includes the description of ';
                $description .= 'your Privacy Studio Page when rendering your form.';
                esc_html_e($description, 'Wirewheel');?>
            </span>
          </div>
          <div class="form-element">
            <input type="submit" name="editform" class="button-primary"
             value="Update"/>
          </div>
        </form>
      <?php endif; ?>
    </div>
        <?php
    }
}
