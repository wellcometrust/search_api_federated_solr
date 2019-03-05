<?php

namespace Drupal\search_api_federated_solr\Form;

/**
 * @file
 * Contains \Drupal\search_api_solr_federated\Form\SearchApiFederatedSolrSearchAppSettingsForm.
 */

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SearchApiFederatedSolrSearchAppSettingsForm.
 *
 * @package Drupal\search_api_federated_solr\Form
 */
class SearchApiFederatedSolrSearchAppSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_api_federated_solr_search_app_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'search_api_federated_solr.search_app.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#validate'][] = [$this, 'formValidationPathValidate'];

    $config = $this->config('search_api_federated_solr.search_app.settings');

    $index_options = [];
    $search_api_indexes = \Drupal::entityTypeManager()->getStorage('search_api_index')->loadMultiple();
    /* @var  $search_api_index \Drupal\search_api\IndexInterface */
    foreach ($search_api_indexes as $search_api_index) {
      $index_options[$search_api_index->id()] = $search_api_index->label();
    }

    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search app path'),
      '#default_value' => $config->get('path'),
      '#description' => $this
        ->t('The path for the search app (Default: "/search-app").'),
    ];

    $form['page_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search results page title'),
      '#default_value' => $config->get('page_title'),
      '#description' => $this
        ->t('The title that will live in the header tag of the search results page (leave empty to hide completely).'),
    ];

    $form['search_index'] = [
      '#type' => 'select',
      '#title' => $this->t('Search API index'),
      '#description' => $this->t('Defines <a href="/admin/config/search/search-api">which search_api index and server</a> the search app should use.'),
      '#options' => $index_options,
      '#default_value' => $config->get('index.id'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [$this, 'getSiteName'],
        'event' => 'change',
        'wrapper' => 'site-name-property',
      ],
    ];

    $form['site_name_property'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'id' => ['site-name-property'],
      ],
      '#value' => $config->get('index.has_site_name_property') ? 'true' : '',
    ];

    $form['search_index_basic_auth'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Search Index Basic Authentication'),
      '#description' => $this->t('If your Solr server is protected by basic HTTP authentication, enter the login data here. This will be accessible to the client in an obscured, but non-secure method. It should, therefore, only provide read access to the index AND be different from that provided when configuring the server in Search API. The Password field is intentionally not obscured to emphasize this distinction.'),
    ];

    $form['search_index_basic_auth']['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $config->get('index.username'),
    ];

    $form['search_index_basic_auth']['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#default_value' => $config->get('index.password'),
    ];

    $form['set_search_site'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Set the "Site name" facet to this site'),
      '#default_value' => $config->get('facet.site_name.set_default'),
      '#description' => $this
        ->t('When checked, only search results from this site will be shown, by default, until this site\'s checkbox is unchecked in the search app\'s "Site name" facet.'),
      '#states' => [
        'visible' => [
          ':input[name="site_name_property"]' => [
            'value' => "true",
          ],
        ],
      ],
    ];

    $form['show_empty_search_results'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show results for empty search'),
      '#default_value' => $config->get('content.show_empty_search_results'),
      '#description' => $this
        ->t(' When checked, this option allows users to see all results when no search term is entered. By default, empty searches are disabled and yield no results.'),
    ];

    $form['no_results_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('No results text'),
      '#default_value' => $config->get('content.no_results'),
      '#description' => $this
        ->t('This text is shown when a query returns no results. (Default: "Your search yielded no results.")'),
    ];

    $form['search_prompt_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search prompt text'),
      '#default_value' => $config->get('content.search_prompt'),
      '#description' => $this
        ->t('This text is shown when no query term has been entered. (Default: "Please enter a search term.")'),
    ];

    $form['rows'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of search results per page'),
      '#default_value' => $config->get('results.rows'),
      '#description' => $this
        ->t('The max number of results to render per search results page. (Default: 20)'),
    ];

    $form['page_buttons'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of pagination buttons'),
      '#default_value' => $config->get('pagination.buttons'),
      '#description' => $this
        ->t('The max number of numbered pagination buttons to show at a given time. (Default: 5)'),
    ];

    /*
     * // OPTIONAL: defaults to false
     * autocomplete.isEnabled
     * // REQUIRED
     * autocomplete.url
     * // OPTIONAL: defaults to false, whether or not to append wildcard to query term
     * autocomplete.appendWildcard
     * // OPTIONAL: defaults to 5, max number of results which should be returned
     * autocomplete.suggestionRows
     * // OPTIONAL: defaults to 2, number of characters *after* which autocomplete results should appear
     * autocomplete.numChars
     * // REQUIRED: show search-as-you-type results ('result', default) or search term ('term') suggestions
     * autocomplete.mode
     *  // OPTIONAL: default set
     * autocomplete.[mode].titleText
     * // OPTIONAL: defaults to false
     * autocomplete.[mode].showDirectionsText
     */

    $form['autocomplete_is_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable autocomplete for the search results page search form'),
      '#default_value' => $config->get('autocomplete.isEnabled'),
      '#description' => $this
        ->t('Checking this will expose more configuration options for autocomplete behavior for the search form on the Search Results page.'),
    ];

    $form['autocomplete'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Search results page > search form: autocompletion'),
      '#description' => $this->t('These options apply to the autocomplete functionality on the search for which appears above the search results on the search results page.  Configure your placed Federated Search Page Form block to add autocomplete to that form.')
    ];

    $form['autocomplete']['url'] = [
      '#type' => 'url',
      '#title' => $this->t('Endpoint URL'),
      '#default_value' => $config->get('autocomplete.url'),
      '#maxlength' => 2048,
      '#size' => 50,
      '#description' => $this
        ->t('The URL where requests for autocomplete queries should be made. Defaults to the url of the  <code>select</code> Request Handler on the server of the selected Search API index.<br />Supports absolute url pattern to any endpoint which returns the expected autocomplete result structure.'),
    ];

    $form['autocomplete']['is_append_wildcard'] = [
      '#type' => 'checkbox',
      '#title' => '<b>' . $this->t('Append a wildcard \'*\' to support partial text search') . '</b>',
      '#default_value' => $config->get('autocomplete.isAppendWildcard'),
      '#description' => $this
        ->t('Check this box to append a wildcard * to the end of the autocomplete query term (i.e. "car" becomes "car+car*").  This option is recommended if your solr config does not add a field(s) with <a href="https://lucene.apache.org/solr/guide/6_6/tokenizers.html" target="_blank">NGram Tokenizers</a> to your index or if your autocomplete <a href="https://lucene.apache.org/solr/guide/6_6/requesthandlers-and-searchcomponents-in-solrconfig.html#RequestHandlersandSearchComponentsinSolrConfig-RequestHandlers" target="_blank">Request Handler</a> is not configured to search those fields.'),
    ];

    $form['autocomplete']['suggestion_rows'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of results'),
      '#default_value' => $config->get('autocomplete.suggestionRows'),
      '#description' => $this
        ->t('The max number of results to render in the autocomplete results dropdown. (Default: 5)'),
    ];

    $form['autocomplete']['num_chars'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of characters after which autocomplete query should execute'),
      '#default_value' => $config->get('autocomplete.numChars'),
      '#description' => $this
        ->t('Autocomplete query will be executed <em>after</em> a user types this many characters in the search query field. (Default: 2)'),
    ];

    $autocomplete_mode = $config->get('autocomplete.mode') || false;

    $form['autocomplete']['mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Result mode'),
      '#description' => $this->t('Type of results the autocomplete response returns: search results (default) or search terms.'),
      '#options' => [
        'result' => $this
          ->t('Search results (i.e. search as you type functionality)'),
        'Search terms (coming soon)' => [],
      ],
      '#default_value' => $autocomplete_mode || 'result',
    ];

    $form['autocomplete']['mode_title_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Results title text'),
      '#size' => 50,
      '#default_value' => $autocomplete_mode ? $config->get('autocomplete.' . $autocomplete_mode . '.titleText') : '',
      '#description' => $this
        ->t('The title text is shown above the results in the autocomplete drop down.  (Default: "What are you interested in?" for Search Results mode and "What would you like to search for?" for Search Term mode.)'),
    ];

    $form['autocomplete']['mode_show_directions'] = [
      '#type' => 'checkbox',
      '#title' => '<b>' . $this->t('Make keyboard directions visible') . '</b>',
      '#default_value' => $config->get('autocomplete.isAppendWildcard'),
      '#description' => $this
        ->t('Check this box to make the autocomplete keyboard usage directions visible in the results dropdown. This option is recommended for sites that want to maximize their accessibility UX for sighted keyboard users. (Default: false)'),
    ];


    $form['#cache'] = ['max-age' => 0];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the search app configuration.
    $config = $this->configFactory->getEditable('search_api_federated_solr.search_app.settings');

    // Set the search app path.
    $path = $form_state->getValue('path');
    $current_path = $config->get('path');
    $rebuild_routes = FALSE;
    if ($path && $path !== $current_path) {
      $config->set('path', $path);
      $rebuild_routes = TRUE;
    }

    // Set the search results page title.
    $page_title = $form_state->getValue('page_title');
    $config->set('page_title', $page_title);

    // Set the search app config setting for the default search site flag.
    $set_search_site = $form_state->getValue('set_search_site');
    $config->set('facet.site_name.set_default', $set_search_site);

    // Set the search app configuration setting for the default search site flag.
    $show_empty_search_results = $form_state->getValue('show_empty_search_results');
    $config->set('content.show_empty_search_results', $show_empty_search_results);

    // Get the id of the chosen index.
    $search_index = $form_state->getValue('search_index');
    // Save the selected index option in search app config (for form state).
    $config->set('index.id', $search_index);

    // Get the index configuration object.
    $index_config = \Drupal::config('search_api.index.' . $search_index);
    $site_name_property = $index_config->get('field_settings.site_name.configuration.site_name');
    $config->set('index.has_site_name_property', $site_name_property ? TRUE : FALSE);

    // Get the id of the chosen index's server.
    $index_server = $index_config->get('server');

    // Get the server url.
    $server_config = \Drupal::config('search_api.server.' . $index_server);
    $server = $server_config->get('backend_config.connector_config');
    // Get the required server config field data.
    $server_url = $server['scheme'] . '://' . $server['host'] . ':' . $server['port'];
    // Check for the non-required server config field data before appending.
    $server_url .= $server['path'] ?: '';
    $server_url .= $server['core'] ? '/' . $server['core'] : '';
    // Append the request handler.
    $server_url .= '/select';

    // Set the search app configuration setting for the solr backend url.
    $config->set('index.server_url', $server_url);

    // Set the Basic Auth username and password.
    $config->set('index.username', $form_state->getValue('username'));
    $config->set('index.password', $form_state->getValue('password'));

    // Set the no results text.
    $config->set('content.no_results', $form_state->getValue('no_results_text'));

    // Set the search prompt text.
    $config->set('content.search_prompt', $form_state->getValue('search_prompt_text'));

    // Set the number of rows.
    $config->set('results.rows', $form_state->getValue('rows'));

    // Set the number of pagination buttons.
    $config->set('pagination.buttons', $form_state->getValue('page_buttons'));

    $config->save();

    if ($rebuild_routes) {
      // Rebuild the routing information without clearing all the caches.
      \Drupal::service('router.builder')->rebuild();
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Get the name of the site.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function getSiteName(array &$form, FormStateInterface $form_state) {
    // Get the id of the chosen index.
    $search_index = $form_state->getValue('search_index');
    // Get the index configuration object.
    $index_config = \Drupal::config('search_api.index.' . $search_index);
    $is_site_name_property = $index_config->get('field_settings.site_name.configuration.site_name') ? 'true' : '';

    $elem = [
      '#type' => 'hidden',
      '#name' => 'site_name_property',
      '#value' => $is_site_name_property,
      '#attributes' => [
        'id' => ['site-name-property'],
      ],
    ];

    return $elem;
  }

  /**
   * Validates that the provided search path is not in use by an existing route.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function formValidationPathValidate(array &$form, FormStateInterface $form_state) {
    $path = $form_state->getValue('path');
    if ($path) {
      // Check if a route with the config path value already exists.
      $router = \Drupal::service('router.no_access_checks');
      $result = FALSE;
      try {
        $result = $router->match($path);
      }
      catch (\Exception $e) {
        // This is what we want, indicates the route path doesn't exist.
      }
      // If the route path exists for something other than the search route,
      // set an error on the form.
      if ($result && $result['_route'] !== "search_api_federated_solr.search") {
        $form_state->setErrorByName('path', t('The path you have entered already exists'));
      }
    }
  }

}
