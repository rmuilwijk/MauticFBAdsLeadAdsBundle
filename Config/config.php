<?php

/*
 * @copyright   2017 Trinoco. All rights reserved
 * @author      Trinoco
 *
 * @link        http://trinoco.nl
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
  'name'        => 'Advertising',
  'description' => 'Enables synchnronization of Facebook Lead Ads as leads into Mautic.',
  'version'     => '1.0',
  'author'      => 'Trinoco',
  'routes'      => [
    'public' => [
      'mautic_plugin_fbadsleadads_subscriber' => [
        'path'         => '/plugin/fbadsleadads/leadform_subscriber',
        'controller'   => 'MauticFBAdsLeadAdsBundle:Public:subscriber',
        'requirements' => [
          'integration' => '.+',
        ],
      ],
    ],
  ],
  'services' => [
    'integrations' => [
      'mautic.integration.fbadsleadads' => [
        'class'     => \MauticPlugin\MauticFBAdsLeadAdsBundle\Integration\FBAdsLeadAdsIntegration::class,
      ],
    ],
  ],
];
