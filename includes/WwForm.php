<?php
if (!defined('ABSPATH')) {
    exit;
}

class WWC_Form
{

    /**
     * Store Global config data.
     */
    public $form_id;

    /**
     * Store API Response data.
     */
    protected $config = '';

    /**
     * Store Recaptcha token url.
     */
    protected $recaptcha_token_url;

    /**
     * Store global config.
     */
    protected $global_config;

    /**
     * Store form config.
     */
    protected $form_config;

    /**
     * Constructor.
     *
     * @param integer $form_id Form ID.
     */
    function __construct($form_id)
    {
        $global_config = json_decode(get_option('wwc_form_config'), 1);
        $recaptcha_url = $global_config['instance_id'] . '/';
        $recaptcha_url .=  $global_config['recaptcha_api_information'];
        $this->recaptcha_token_url = $recaptcha_url;
        $api_url = $global_config['instance_id'] . '/';
        $api_url .=  $global_config['data_path'] . '/' . $form_id;
        $api_response = wp_remote_get($api_url);
        $status_code = wp_remote_retrieve_response_code($api_response);
        $body = wp_remote_retrieve_body($api_response);
        $respone = '';
        if ($status_code == 200) {
            $response = json_decode($body);
            $this->config = $response;
        }
        $this->form_config = json_decode(get_option('wwc_form_' . $form_id), 1);
        $this->global_config = $global_config;
    }

    /**
     * Build Wirewheel form.
     *
     * @return string
     *   Returns the form HTML.
     */
    public function buildForm()
    {
        if (isset($this->global_config['default_styling'])) {
            $css_path = plugins_url('../css/wirewheel-form.css', __FILE__);
            wp_enqueue_style('wirewheen_form', $css_path);
        }

        // Different Request Types.
        $request_types = $this->getRequestTypes();

        if (null === $request_types) {
            return;
        }

        // Form Fields.
        $form_fields = $this->getFormFields();

        // Form header.
        $form_header = $this->getFormHeaders();

        // Form Header
        $time_now = time();
        $output = "<div class='wirewheel-form-wrapper container'>";
        $output .= "<form method='post' class='wirewheel-form-{$time_now}'>";
        $header = '';
        $output .= wp_nonce_field('ww_nonce', 'ww_nonce');
        $error_message = '';
        $success_message = '';
        $values = [];

        // Fetch the Header information.
        if (isset($this->form_config['show_title'])) {
            $header .= '<h3>' . $form_header['header'] . '</h3>';
        }

        if (isset($this->form_config['show_description'])) {
            $header .= '<div class="header-description">';
            $header .= $form_header['description'];
            $header .= '</div>';
        }

        if (!empty($header)) {
            $output .= "<div class='header-wrapper'>{$header}</div>";
        }

        if (isset($_POST['submit'])
            && wp_verify_nonce(sanitize_key($_POST['ww_nonce']), 'ww_nonce')
        ) {
            $errors = $this->validate($_POST);
            if (!empty($errors)) {
                $output .= '<div class="errors"><ul>';
                foreach ($errors as $error) {
                    $output .= "<li>{$error}</li>";
                }
                $output .= '</div></ul>';
            } else {
                // Sanitize data and Proceed with submission.
                foreach ($_POST as $key => $value) {
                    if (is_array($value)) {
                        $value = implode(',', $value);
                    }
                    $values[$key] = sanitize_text_field($value);
                }
                $data = $this->getRequestData($values);
                $response = $this->sendApiResponse($data);
                if (strpos($response, 'Error') !== false) {
                    $output .= $response;
                } else {
                    $output .= '<div class="dsar-response-wrapper">';
                    $output .= '<div class="dsar-confirmation confirmation-msg">' . $this->config->srrConfig->successMessage . '</div>';
                    $output .= '<div class="dsar-id"> Here is your request ID: ' . esc_html($response) . '</div>';
                    $output .= '<div class="submit-new-request"><a href onclick="window.location.reload(true);">' . $this->global_config['submit_new_request_label'] . '</a></div>';
                    $output .= '</div></form></div>';
                    return $output;
                }
            }
        }

        // Build form elements.
        $form_request_types = [];
        $form_items = [];
        // Get the Title for the Categories.
        foreach ($request_types as $key => $request_type) {
            $form_request_types[$key] = $this->buildRequestTypeField($key, $request_type);
        }

        // Enable submit only if one of the request type is selected.
        $request_type_state = [];
        foreach (array_keys($request_types) as $type) {
            $request_type_state[] = $type;
        }

        if (!empty($form_fields)) {
            foreach ($form_fields as $field) {
                $api_identfier = $field['apiIdentifier'];
                $states = [];
                $field_applicable_to = \array_keys($field['ids']);

                if (count($field_applicable_to) > 1) {
                    foreach ($field_applicable_to as $type) {
                        $states[] = $type;
                    }
                } else {
                    foreach ($request_types as $key => $request_type) {
                        unset($form_request_types[$key]);
                    }
                }

                $type = $field['type'];
                if ($type == 'select' || $type == 'radio' || $type == 'checkbox') {
                    $options = [];
                    // Create the Options array.
                    foreach ($field['items'] as $item) {
                        $options[$item] = $item;
                    }
                    $form_items[$api_identfier]['#options'] = $options;
                }
                $form_items[$api_identfier]['#type'] = $type;
                $form_items[$api_identfier]['#label'] = $field['label'];
                $form_items[$api_identfier]['#required'] = $field['required'];
                $states_data = implode(',', $states);
                $form_items[$api_identfier]['#visible_for_requests'] = $states_data;

                // Field Rule Validation.
                if (!empty($field['rules'])) {
                    $rule_options = $field['ruleOptions'];
                    if ($field['type'] === 'text') {
                        if (in_array('number-only', $field['rules'])) {
                            $form_items[$api_identfier]['#pattern'] = "\d*";
                            $error = 'Number values only.';
                            $form_items[$api_identfier]['#description'] = $error;
                        }
                        if (in_array('letters-only', $field['rules'])) {
                            $form_items[$api_identfier]['#pattern'] = "^[a-zA-Z]+$";
                            $error = 'Alphabetical characters only.';
                            $form_items[$api_identfier]['#description'] = $error;
                        }
                        if (!empty($rule_options->limit)) {
                            $limit = $rule_options->limit;
                            $form_items[$api_identfier]['#maxlength'] = $limit;
                        }
                    }

                    if ($field['type'] === 'date') {
                        if (!empty($rule_options->min)) {
                            $form_items[$api_identfier]['#min'] = $rule_options->min;
                        }
                        if (!empty($rule_options->max)) {
                            $form_items[$api_identfier]['#max'] = $rule_options->max;
                        }
                    }
                }
            }
        }

        // Render Request types
        $count = 0;
        foreach ($form_request_types as $request_type) {
            if (($count % 2) === 0) {
                $output .= '<div class="request-types wirewheel-even">';
            } else {
                $output .= '<div class="request-types wirewheel-odd">';
            }
            $output .= '<div class="form-item form-type-checkbox">';
            $output .= $request_type;
            $output .= '</div></div>';
            $count++;
        }
        $request_type_state = implode(',', $request_type_state);
        $output .= "<div class='wirewheel-col-12'>";
        $output .= "<details class='form-wrapper hidden' open='open'>";
        $output .= "<div class='details-wrapper details-wrapper'>";
        // Render form fields.
        foreach ($form_items as $key => $form_item) {
            $required = '';
            $required_symbol = '';
            if ($form_item['#required']) {
                $required = 'required';
                $required_symbol = '*';
            }
            $max_length = '';
            if (isset($form_item['#maxlength'])) {
                $max_length = $form_item['#maxlength'];
            }
            $pattern = '';
            if (isset($form_item['#pattern'])) {
                $pattern = "pattern='{$form_item['#pattern']}'";
            }

            $output .= "<div class='wirewheel-col-6 form-element-wrapper hidden' ";
            $output .= "data-visible-for='{$form_item['#visible_for_requests']}'>";
            $output .= "<div class='form-item'>";
            $output .= "<label for='{$form_item['#type']}' ";
            $output .= "class='form-{$required}'>";
            $output .= "{$form_item['#label']} {$required_symbol}</label>";

            if ($form_item['#type'] === 'checkbox') {
                $output .= "<fieldset class='js-form-item form-wrapper'>";
                $output .= "<div class='fieldset-wrapper'>";
                $output .= "<div class='form-checkboxes'>";
                foreach ($form_item['#options'] as $option) {
                    $output .= "<div class='form-item'>";
                    $output .= "<input name='{$key}[]' type='{$form_item['#type']}' ";
                    $output .= "value='{$option}' class='form-checkbox' ";
                    $output .= "data-required='{$required}' {$required}>";
                    $output .= "<label for='{$option}'>{$option}</label>";
                    $output .= "</div>";
                }
                $output .= '</div></div></fieldset>';
            } else if ($form_item['#type'] === 'radio') {
                $output .= "<fieldset class='js-form-item form-wrapper'>";
                $output .= "<div class='fieldset-wrapper'><div class='form-radios'>";
                foreach ($form_item['#options'] as $option) {
                    $output .= "<div class='form-item form-type-radio'>";
                    $output .= "<input name='{$key}' type='{$form_item['#type']}' ";
                    $output .= "value='{$option}' class='form-radio' ";
                    $output .= "data-required='{$required}' {$required}>";
                    $output .= "<label for='{$option}'>{$option}</label>";
                    $output .= "</div>";
                }
                $output .= '</div></div></fieldset>';
            } else if ($form_item['#type'] === 'select') {
                $output .= "<select name='{$key}' id='{$key}' class='form-select' ";
                $output .= "data-required='{$required}' {$required}>";
                $output .= "<option value='' selected>- Select -</option>";
                foreach ($form_item['#options'] as $option) {
                    $output .= "<option value='{$option}'>{$option}</option>";
                }
                $output .= '</select>';
            } else if ($form_item['#type'] === 'date') {
                $date_min = '';
                $date_max = '';
                if (isset($form_item['#min'])) {
                    $date_min = $form_item['#min'];
                }
                if (isset($form_item['#max'])) {
                    $date_max = $form_item['#max'];
                }
                $output .= "<input name='{$key}' type='{$form_item['#type']}' ";
                $output .= "data-required='{$required}' {$required} ";
                $output .= "min='{$date_min}' max='{$date_max}'>";
            } else {
                $output .= "<input name='{$key}' type='{$form_item['#type']}' ";
                $output .= "placeholder='{$form_item['#label']}' ";
                $output .= "data-required='{$required}' ";
                $output .= "{$required} maxlength='{$max_length}' {$pattern}>";
            }

            if (isset($form_item['#description'])) {
                $output .= "<div class='description'>{$form_item['#description']}";
                $output .= "</div>";
            }

            $output .= '</div></div>';
        }

        // Attach Recaptcha if applicable.
        if ($form_header['enableRecaptcha']) {
            $recaptcha_token = $this->getRecaptchaToken();
            if (!empty($recaptcha_token)) {
                $google_js = 'https://www.google.com/recaptcha/api.js';
                $google_js .= '?onload=onloadCallback&render=explicit';
                wp_enqueue_script(
                    'google_recaptcha',
                    $google_js
                );
                wp_add_inline_script(
                    'google_recaptcha',
                    "var widgetId; var onloadCallback = function () { widgetId = grecaptcha.render('recaptcha-setup', { 'sitekey': '" . $recaptcha_token . "', 'theme': 'light'});};"
                );
                $output .= '<div id="recaptcha-setup" class="recaptcha" ';
                $output .= 'data-callback="recaptcha_filled" ';
                $output .= 'data-expired-callback="recaptcha_expired"></div>';
            }
        }
        $output .= '<input type="hidden" name="form_id">';
        $output .= '<input type="hidden" name="requests">';

        $output .= '<div class="wirewheel-submit"><div class="form-actions">';
        $output .= '<input name="submit" type="submit" value="Submit"></div></div>';
        $output .= '</div></div>';
        $output .= '</form></div>';
        return $output;
    }

    /**
     * Build Request type field.
     *
     * @param $key          Field Key.
     * @param $request_type Different Request Types.
     *
     * @return $output HTML.
     */
    function buildRequestTypeField($key, array $request_type)
    {
        $output = "<input type='checkbox' name='{$key}' id='edit-{$key}'";
        $output .= "value='1' class='form-checkbox'>";
        $output .= "<label for='edit-{$key}' class='form-item__label option'>";
        $output .= "<span>" . $request_type['title'] . '</span>';
        $output .= "<div class='description'>{$request_type['description']}</label></div>";
        return $output;
    }

    /**
     * Form Validation.
     *
     * @param $values Field elements.
     *
     * @return array
     *   List of all the Errors in the array format.
     */
    protected function validate($values)
    {
        $errors = [];
        $form_fields = $this->getFormFields();
        foreach ($form_fields as $field) {
            if (!empty($field['rules'])) {
                $api_identfier = $field['apiIdentifier'];
                $rule_options = $field['ruleOptions'];
                if ($field['type'] === 'text') {
                    if (in_array('number-only', $field['rules'])
                        && !ctype_digit($values[$api_identfier])
                    ) {
                        $error_msg = 'Numbers only allowed for field ';
                        $errors[] = $error_msg . $field['label'];
                    }
                    if (in_array('letters-only', $field['rules'])
                        && !ctype_alpha($values[$api_identfier])
                    ) {
                        $error_msg = 'Alphabetical characters only for field ';
                        $errors[] = $error_msg . $field['label'];
                    }
                }
            }
        }
        if (isset($values['g-recaptcha-response'])
            && empty($values['g-recaptcha-response'])
        ) {
            $errors[] = 'Please select Captcha';
        }
        return $errors;
    }

    /**
     * Helper method to get recaptcha token.
     *
     * @return string
     *   Recaptcha Site Key.
     */
    function getRecaptchaToken()
    {

        $recaptcha_site_key = '';
        $recaptcha_token_url = $this->recaptcha_token_url;
        $recaptcha_token_response = wp_remote_get($recaptcha_token_url);
        $code = wp_remote_retrieve_response_code($recaptcha_token_response);
        $recaptcha_body = wp_remote_retrieve_body($recaptcha_token_response);
        if ($code == 200) {
            $recaptcha_body = json_decode($recaptcha_body);
            $recaptcha_site_key = $recaptcha_body->recaptchaSiteKey;
        }
        return $recaptcha_site_key;
    }

    /**
     * Get all type of requests.
     *
     * @return array
     *   List of different Request Types.
     */
    protected function getRequestTypes()
    {
        $response = $this->config;
        if (isset($response->srrConfig) && isset($response->srrConfig->requests)) {
            $requests = $response->srrConfig->requests;

            // Get the request information.
            $request_information = $this->getTitleDescriptionRequestTypes();

            $request_types = [];
            foreach ($requests as $request) {
                if (!array_key_exists($request->requestType, $request_information)) {
                    continue;
                }
                $type = $request_information[$request->requestType];
                $request_types[$request->requestType] = $type;
            }
        }
        return $request_types;
    }

    /**
     * Get the Title and Description for all the Request Types.
     *
     * @return array
     *   Description for the different Request Types.
     */
    public function getTitleDescriptionRequestTypes()
    {
        $opt_out_desc = 'Withdraw consent for the processing ';
        $opt_out_desc .= 'of your personal data.';
        $dont_sell_desc = 'Withdraw consent for the sale of ';
        $dont_sell_desc .= 'your personal information.';
        $access_desc = 'Request information on which types of ';
        $access_desc .= 'data have been collected about you.';
        $correction_desc = 'Request we rectify inaccurate ';
        $correction_desc .= 'or incomplete personal data about you.';
        $portability_desc = 'Request we provide your personal data ';
        $portability_desc .= 'in a machine-readable format.';

        $request_information = [
        'access' => [
        'title' => 'Access',
        'description' => 'Request access to your personal information.',
        ],
        'deletion' => [
        'title' => 'Deletion',
        'description' => 'Request your user data be deleted from our systems.',
        ],
        'optOut' => [
        'title' => 'Opt-out of Data Processing',
        'description' => $opt_out_desc,
        ],
        'optOut-data-processing' => [
        'title' => 'Opt-out of Data Processing',
        'description' => 'Stop processing my data.',
        ],
        'do-not-sell' => [
        'title' => 'Do not sell my personal information',
        'description' => $dont_sell_desc,
        ],
        'category-access' => [
        'title' => 'Category Access',
        'description' => $access_desc,
        ],
        'correction' => [
        'title' => 'Correction',
        'description' => $correction_desc,
        ],
        'portability' => [
        'title' => 'Portability',
        'description' => $portability_desc,
        ],
        ];

        return $request_information;
    }

    /**
     * Get form fields for a given requestType.
     *
     * @return array
     *   Response array.
     */
    public function getFormFields()
    {
        $response = $this->config;
        if (isset($response->srrConfig) && isset($response->srrConfig->requests)) {
            $requests = $response->srrConfig->requests;
            foreach ($requests as $request) {
                foreach ($request->fields as $dataset) {
                    $data = (array) $dataset;
                    $apiIdentifier = $data['apiIdentifier'];

                    if (!empty($elements[$apiIdentifier])) {
                        $type = $request->requestType;
                        $elements[$apiIdentifier]['ids'][$type] = $data['_id'];
                    } else {
                        $data['ids'] = [
                            $request->requestType => $data['_id'],
                        ];
                        $elements[$apiIdentifier] = $data;
                    }
                }
            }
        }
        return $elements;
    }

    /**
     * Get Requests Data for submission to API.
     *
     * @param $values URL for the API.
     *
     * @return array
     *   Response $data.
     */
    public function getRequestData($values)
    {
        $form_fields = $this->getFormFields();
        $requests = explode(',', $values['requests']);
        $provided_fields = [];
        $data = [];
        $data['data']['recaptchaToken'] = $values['g-recaptcha-response'];
        foreach ($form_fields as $key => $field) {
            if (isset($field['submissionEmail']) && isset($values[$key])) {
                $data['data']['primaryEmail'] = $values[$key];
            }
            // If submitted field matches with form fields.
            if (isset($values[$key]) && !empty($values[$key])) {
                // Collect data for each request.
                foreach ($requests as $request) {
                    $provided_fields[] = [
                        'value' => $values[$key],
                        'label' => $field['label'],
                        '_id' => $field['ids'][$request],
                    ];
                }
            }
        }
        $data['data']['action'] = $values['requests'];
        $data['data']['providedFields'] = $provided_fields;
        $data['data']['providedRequests'] = $requests;
        $data['data']['locale'] = 'en';
        $data['meta'] = [
            'id' => $this->config->srrConfig->settingsId,
            'type' => 'dsar'
        ];
        return $data;
    }

    /**
     * Get the Form Headers.
     *
     * @return array
     *   Header information.
     */
    public function getFormHeaders()
    {
        if (!isset($this->config->srrConfig)) {
            return [
                'header' => '',
                'description' => '',
                'successMessage' => ''
            ];
        }
        $response = $this->config->srrConfig;

        // Header Text.
        if (isset($response->header)) {
            $header_information['header'] = $response->header;
        }

        // Description for the form.
        if (isset($response->description)) {
            $header_information['description'] = $response->description;
        }

        // Success Message from the API.
        if (isset($response->successMessage)) {
            $header_information['successMessage'] = $response->successMessage;
        }

        // Recaptcha Enabled or not.
        $header_information['enableRecaptcha'] = $response->enableRecaptcha;

        return $header_information;
    }

    /**
     * Send Post API Response.
     *
     * @param array $data Data to sent to API.
     *
     * @return object
     *   Response object.
     */
    public function sendApiResponse(array $data)
    {
        $config = $this->global_config;
        $url = $config['instance_id'] . '/' . $config['data_end_point'];
        $params = [
            'body' => \json_encode($data),
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ];
        $response = wp_remote_post($url, $params);
        $error = 'Error: Something went wrong, Please try again later';
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            return $error . ': ' . $error_message;
        } else {
            $status_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $body = json_decode($body, true);
            if (in_array($status_code, [200, 201, 202]) && isset($body['rootId'])) {
                return $body['rootId'];
            }

            return $error . ", Status code:" . $status_code;
        }
    }
}
