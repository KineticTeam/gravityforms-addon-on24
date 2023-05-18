<?php

GFForms::include_feed_addon_framework();

class GFOn24AddOn extends GFFeedAddOn
{
    protected $_version = GF_ON24_ADDON_VERSION;
    protected $_min_gravityforms_version = '1.9.16';
    protected $_slug = 'gravityforms-addon-on24';
    protected $_path = 'gravityforms-addon-on24/gravityforms-addon-on24.php';
    protected $_full_path = __FILE__;
    protected $_title = 'Gravity Forms On24 Add-On';
    protected $_short_title = 'On24 Add-On';

    private static $_instance = null;

    /**
     * Get an instance of this class.
     *
     * @return GFOn24AddOn
     */
    public static function get_instance()
    {
        if (self::$_instance == null) {
            self::$_instance = new GFOn24AddOn();
        }

        return self::$_instance;
    }

    /**
     * Plugin starting point. Handles hooks and loading of language files.
     */
    public function init()
    {
        parent::init();
    }

    /**
     * Process the feed e.g. subscribe the user to a list.
     *
     * @param array $feed The feed object to be processed.
     * @param array $entry The entry object currently being processed.
     * @param array $form The form object currently being processed.
     *
     * @return bool|void
     */
    public function process_feed($feed, $entry, $form)
    {
        // Get the event ID and key by grabbing the last two sections of the URL
        $parsedFeed = array_slice(explode('/', $feed['meta']['eventUrl']), -2, 2);

        if (count($parsedFeed) !== 2 || ! $parsedFeed[0] || ! $parsedFeed[1]) {
            $this->log_debug('[On24] Invalid $eventUrl: ' . print_r($feed['meta']['eventUrl'], true));
            return;
        }

        $apiUrl = 'https://event.on24.com/utilApp/r?eventid='
            . $parsedFeed[0] . '&key='
            . $parsedFeed[1];

        $apiUrl = filter_var($apiUrl, FILTER_SANITIZE_URL);

        if (filter_var($apiUrl, FILTER_VALIDATE_URL) === false) {
            $this->log_debug('[On24] Invalid $apiUrl: ' . print_r($apiUrl, true));
            return;
        }

        // Retrieve the name  => value pairs for all fields mapped in the 'mappedFields' field map.
        $field_map = $this->get_field_map_fields($feed, 'mappedFields');

        // Loop through the fields from the field map setting building an array of values to be passed to the third-party service.
        $merge_vars = [];

        foreach ($field_map as $name => $field_id) {
            // Get the field value for the specified field id
            $merge_vars[$name] = $this->get_field_value($form, $entry, $field_id);
        }

        // Send the values to the third-party service.
        $this->log_debug('[On24] Sending: ' . print_r($merge_vars, true));
        $request  = new WP_Http();
        $response = $request->post($apiUrl, ['body' => $merge_vars]);
        $this->log_debug('[On24] Received: ' . print_r($response, true));

        // Add note to the entry
        $this->add_note(
            $entry['id'],
            "Data posted to {$apiUrl}\r\n"
                . print_r($response['response'], true)
        );

        return;
    }

    /**
     * Return the scripts which should be enqueued.
     *
     * @return array
     */
    public function scripts()
    {
        // $scripts = array(
        //     array(
        //         'handle' => 'my_script_js',
        //         'src' => $this->get_base_url() . '/js/my_script.js',
        //         'version' => $this->_version,
        //         'deps' => array( 'jquery' ),
        //         'strings' => array(
        //             'first' => esc_html__('First Choice', 'on24addon'),
        //             'second' => esc_html__('Second Choice', 'on24addon'),
        //             'third' => esc_html__('Third Choice', 'on24addon'),
        //         ),
        //         'enqueue' => array(
        //             array(
        //                 'admin_page' => array( 'form_settings' ),
        //                 'tab' => 'on24addon',
        //             ),
        //         ),
        //     ),
        // );

        $scripts = [];

        return array_merge(parent::scripts(), $scripts);
    }

    /**
     * Return the stylesheets which should be enqueued.
     *
     * @return array
     */
    public function styles()
    {
        // $styles = [
        //     [
        //         'handle' => 'styles',
        //         'src' => $this->get_base_url() . '/css/styles.css',
        //         'version' => $this->_version,
        //         'enqueue' => [
        //             ['field_types' => ['poll',],],
        //         ],
        //     ],
        // ];

        $styles = [];

        return array_merge(parent::styles(), $styles);
    }

    /**
     * Creates a custom page for this add-on.
     */
    public function plugin_page()
    {
        echo <<<EOT
        <p style="max-width: 65ch;">Enable On24 for a particular form by adding a new feed in the form's settings, under "On24 Add-On." There you can set the On24 event ID and key, map form fields to On24 fields and add conditional processing rules.</p>
        <p><a href="?page=gf_edit_forms">Browse forms</a></p>
        EOT;
    }

    /**
     * Configures the settings which should be rendered on the add-on settings tab.
     *
     * @return array
     */
    // public function plugin_settings_fields()
    // {
    //     // return [
    //     //     [
    //     //         'title' => esc_html__('On24 Settings', 'on24addon'),
    //     //         'fields' => [
    //     //             [
    //     //                 'name' => 'textbox',
    //     //                 'tooltip' => esc_html__('This is the tooltip', 'on24addon'),
    //     //                 'label' => esc_html__('This is the label', 'on24addon'),
    //     //                 'type' => 'text',
    //     //                 'class' => 'small',
    //     //             ],
    //     //         ],
    //     //     ],
    //     // ];
    // }

    /**
     * Configures the settings which should be rendered on the feed edit page in the Form Settings > On24 Add-On area.
     *
     * @return array
     */
    public function feed_settings_fields()
    {
        return [[
            'title' => esc_html__('On24 Settings', 'on24addon'),
            'fields' => [
                [
                    'label' => esc_html__('Feed name', 'on24addon'),
                    'type' => 'text',
                    'name' => 'feedName',
                    'tooltip' => esc_html__('Enter a name to identify this feed. It is not public.', 'on24addon'),
                    'class' => 'small',
                    'required' => 1,
                ],
                [
                    'label' => esc_html__('Webinar Audience Event URL', 'on24addon'),
                    'type' => 'textarea',
                    'name' => 'eventUrl',
                    'tooltip' => esc_html__('Copy the event\'s Audience URL from the "Event URLs" section in On24.', 'on24addon'),
                    'class' => 'small',
                    'required' => 1,
                    'allow_html' => true,
                ],
                [
                    'name' => 'mappedFields',
                    'label' => esc_html__('Map Fields', 'on24addon'),
                    'type' => 'field_map',
                    'field_map' => [
                        [
                            'name' => 'email',
                            'label' => esc_html__('Email', 'on24addon'),
                            'required' => 1,
                            'field_type' => [ 'email', 'hidden' ],
                            // 'tooltip' => esc_html__('This is the tooltip', 'on24addon'),
                        ],
                        [
                            'name' => 'firstname',
                            'label' => esc_html__('First Name', 'on24addon'),
                            'required' => 0,
                        ],
                        [
                            'name' => 'lastname',
                            'label' => esc_html__('Last Name', 'on24addon'),
                            'required' => 0,
                        ],
                        [
                            'name' => 'company',
                            'label' => esc_html__('Company', 'on24addon'),
                            'required' => 0,
                        ],
                        [
                            'name' => 'work_phone',
                            'label' => esc_html__('Work Phone', 'on24addon'),
                            'required' => 0,
                            'field_type' => 'phone',
                        ],
                        [
                            'name' => 'state',
                            'label' => esc_html__('State', 'on24addon'),
                            'required' => 0,
                        ],
                        [
                            'name' => 'std1',
                            'label' => esc_html__('std1', 'on24addon'),
                            'required' => 0,
                        ],
                    ],
                ],
                [
                    'name' => 'condition',
                    'label' => esc_html__('Conditional Processing', 'on24addon'),
                    'type' => 'feed_condition',
                    'checkbox_label' => esc_html__('Enable conditional processing', 'on24addon'),
                    'instructions' => esc_html__('Only send to On24 if', 'on24addon'),
                ],
            ],
        ]];
    }

    /**
     * Configures which columns should be displayed on the feed list page.
     *
     * @return array
     */
    public function feed_list_columns()
    {
        return array(
            'feedName' => esc_html__('Feed', 'on24addon'),
            'eventUrl' => esc_html__('On24 URL', 'on24addon'),
        );
    }

    /**
     * Prevent feeds being listed or created if an api key isn't valid.
     *
     * @return bool
     */
    public function can_create_feed()
    {
        // // Get the plugin settings.
        // $settings = $this->get_plugin_settings();

        // // Access a specific setting e.g. an api key
        // $key = rgar($settings, 'apiKey');

        return true;
    }
}
