<?php

/*
 * @copyright   2017 Trinoco. All rights reserved
 * @author      Trinoco
 *
 * @link        http://trinoco.nl
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticFBAdsLeadAdsBundle\Controller;

use FacebookAds\Api;
use FacebookAds\Object\AdAccount;
use FacebookAds\Object\Fields\LeadFields;
use FacebookAds\Object\Lead;
use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\LeadBundle\Entity\Tag;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class class PublicController extends CommonController.
 */
class PublicController extends CommonController
{
  public function subscriberAction()
  {
    $integration       = 'FBAdsLeadAds';
    $integrationHelper = $this->get('mautic.helper.integration');

    /** @var \MauticPlugin\MauticFBAdsLeadAdsBundle\Integration\FBAdsLeadAdsIntegration $integrationObject */
    $integrationObject = $integrationHelper->getIntegrationObject($integration);

    $keys = $integrationObject->getDecryptedApiKeys();

    $challenge = $this->request->get('hub_challenge', '');
    $verify_token = $this->request->get('hub_verify_token', '');

    if ($verify_token === $keys['verify_token']) {
      return new Response($challenge);
    }

    $content = $this->request->getContent();
    $data = json_decode($content);

    $api = Api::init($keys[$integrationObject->getClientIdKey()], $keys[$integrationObject->getClientSecretKey()], $keys['mapi_access_token']);
    $account_id = 'act_' . $keys[$integrationObject->getAdAccountIdKey()];

    $account = new AdAccount($account_id);
    $forms = $account->getLeadGenForms();
    $form_names = array();
    foreach ($forms as $form) {
      $form_data = $form->getData();
      $form_names[$form_data['id']] = $form_data['name'];
    }

    foreach($data->entry[0]->changes as $change) {
      /**
       * "0" => array(
       *   "field" => "leadgen",
       *   "value" => array(
       *     "leadgen_id" => 123123123123,
       *     "page_id" => 123123123,
       *     "form_id" => 12312312312,
       *     "adgroup_id" => 12312312312,
       *     "ad_id" => 12312312312,
       *     "created_time" => 1440120384
       *   )
       * ),
       */
      if ($change->field == 'leadgen') {
        $lead = new Lead($change->value->leadgen_id);

        $lead->read([
          LeadFields::AD_NAME,
          LeadFields::ADSET_NAME,
          LeadFields::CAMPAIGN_NAME,
          LeadFields::FIELD_DATA,
          LeadFields::FORM_ID,
        ]);

        $lead_values = array();
        foreach ($lead->field_data as $row) {
          $lead_values[$row['name']] = $row['values'][0];
        }

        if (isset($lead_values['work_email']) && empty($lead_values['email'])) {
          $lead_values['email'] = $lead_values['work_email'];
          unset($lead_values['work_email']);
        }

        // Map the data to a actual lead.
        $info = $integrationObject->mapLead($lead_values);
        $mauticLead = $integrationObject->getMauticLead($info, FALSE);

        // Setup the tags.
        $tags = array(
          'Facebook Ads: Lead Ad',
          "Ad: " . $lead->ad_name,
          "Adset: " . $lead->adset_name,
          "Campaign: " . $lead->campaign_name,
          "Form: " . $form_names[$lead->form_id],
        );
        $tag_entities = $this->getModel('lead')->getTagRepository()->getTagsByName($tags);
        foreach ($tag_entities as $tag_name => $entity) {
          unset($tags[array_search($tag_name, $tags)]);
        }
        foreach ($tags as $tag) {
          $tagEntity = new Tag();
          $tagEntity->setTag(InputHelper::clean($tag));
          $tag_entities[] = $tagEntity;
        }
        foreach ($tag_entities as $entity) {
          $mauticLead->addTag($entity);
        }

        // Save the lead.
        $this->getModel('lead')->saveEntity($mauticLead, false);
      }
    }

    return new Response('OK');
  }
}
