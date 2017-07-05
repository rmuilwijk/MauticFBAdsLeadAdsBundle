<?php

/*
 * @copyright   2017 Trinoco. All rights reserved
 * @author      Trinoco
 *
 * @link        http://trinoco.nl
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticFBAdsLeadAdsBundle\Integration;

use FacebookAds\Http\RequestInterface;
use FacebookAds\Object\AdAccount;
use FacebookAds\Object\Fields\LeadgenFormFields;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use FacebookAds\Api;

/**
 * Class FBAdsLeadAdsIntegration.
 */

class FBAdsLeadAdsIntegration extends AbstractIntegration
{
  public function getName()
  {
    return 'FBAdsLeadAds';
  }

  /**
   * Name to display for the integration. e.g. iContact  Uses value of getName() by default.
   *
   * @return string
   */
  public function getDisplayName()
  {
    return 'Facebook Ads Lead Ads';
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthenticationType()
  {
    return 'oauth2';
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthenticationUrl()
  {
    return 'https://www.facebook.com/dialog/oauth';
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessTokenUrl()
  {
    return 'https://graph.facebook.com/oauth/access_token';
  }

  /**
   * Get the array key for clientId.
   *
   * @return string
   */
  public function getClientIdKey()
  {
    return 'client_id';
  }

  /**
   * Get the array key for client secret.
   *
   * @return string
   */
  public function getClientSecretKey()
  {
    return 'client_secret';
  }

  /**
   * @return string
   */
  public function getAuthScope()
  {
    return 'manage_pages';
  }

  /**
   * Get the array key for client secret.
   *
   * @return string
   */
  public function getAdAccountIdKey() {
    return 'ad_account_id';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredKeyFields()
  {
    return [
      'client_id'      => 'mautic.integration.keyfield.FBAds.client_id',
      'client_secret'      => 'mautic.integration.keyfield.FBAds.client_secret',
      'mapi_access_token'    => 'mautic.integration.keyfield.FBAds.mapi_access_token',
      'verify_token' => 'mautic.integration.keyfield.FBAds.verify_token',
      'ad_account_id'    => 'mautic.integration.keyfield.FBAds.ad_account_id',
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @param string $data
   * @param bool   $postAuthorization
   *
   * @return mixed
   */
  public function parseCallbackResponse($data, $postAuthorization = false)
  {
    if ($postAuthorization) {
      $json = json_decode($data, true);

      $keys = $this->getDecryptedApiKeys();

      // Retrieve the long living access tokens.
      $api = Api::init($keys[$this->getClientIdKey()], $keys[$this->getClientSecretKey()], $json['access_token']);
      /** @var \FacebookAds\Http\Response $accounts_res */
      $accounts_res = Api::instance()->call('/me/accounts');
      $accounts_data = $accounts_res->getContent();
      $accounts = array();
      foreach ($accounts_data['data'] as $page) {
        $accounts[$page['id']] = $page['access_token'];
      }

      // Setup the Marketing API.
      $api = Api::init($keys[$this->getClientIdKey()], $keys[$this->getClientSecretKey()], $keys['mapi_access_token']);
      $account_id = 'act_' . $keys[$this->getAdAccountIdKey()];
      $account = new AdAccount($account_id);

      // Retrieve all pages which have lead gen forms.
      $pages = array();
      /** @var \FacebookAds\Object\LeadgenForm[] $forms_res */
      $forms_res = $account->getLeadGenForms([LeadgenFormFields::PAGE_ID]);
      foreach ($forms_res as $form) {
        $data = $form->getData();
        $pages[$data['page_id']] = $data['page_id'];
      }

      // Add to the subscribed apps.
      foreach ($pages as $page_id) {
        if (isset($accounts[$page_id])) {
          $api = Api::init($keys[$this->getClientIdKey()], $keys[$this->getClientSecretKey()], $accounts[$page_id]);
          $res = $api->call('/' . $page_id . '/subscribed_apps', RequestInterface::METHOD_POST);
        }
      }

      // @todo, Add subscribe to the App. Manually in README.md now.
    } else {
      $json = json_decode($data);
    }

    return $json;
  }

  /**
   * @param $endpoint
   *
   * @return string
   */
  public function getApiUrl($endpoint)
  {
    return "https://graph.facebook.com/$endpoint";
  }

  /**
   * Get available company fields for choices in the config UI.
   *
   * @param array $settings
   *
   * @return array
   */
  public function getFormCompanyFields($settings = [])
  {
    return [];
  }

  /**
   * @param array $settings
   *
   * @return array|mixed
   */
  public function getFormLeadFields($settings = [])
  {
    return $this->getFormFieldsByObject('contacts', $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableLeadFields($settings = [])
  {
    $fields = [
      'about'         => ['type' => 'string'],
      'birthday'      => ['type' => 'string'],
      'email'         => ['type' => 'string'],
      'work_email'    => ['type' => 'string'],
      'company_name'  => ['type' => 'string'],
      'first_name'    => ['type' => 'string'],
      'gender'        => ['type' => 'string'],
      'last_name'     => ['type' => 'string'],
      'locale'        => ['type' => 'string'],
      'middle_name'   => ['type' => 'string'],
      'name'          => ['type' => 'string'],
      'timezone'      => ['type' => 'string'],
      'website'       => ['type' => 'string'],
    ];

    foreach ($fields as $field_id => $field) {
      $fields[$field_id]['label'] = $this->translator->trans('mautic.integration.fbadsleadads.'.$field_id);
    }

    return $fields;
  }

  /**
   * @param $object
   *
   * @return array|mixed
   */
  protected function getFormFieldsByObject($object, $settings = [])
  {
    $settings['feature_settings']['objects'] = [$object => $object];
    return $this->getAvailableLeadFields($settings);
  }

  public function mapLead($data) {
    return $this->matchUpData($data);
  }
}
