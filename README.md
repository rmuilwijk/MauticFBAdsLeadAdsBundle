# MauticFBAdsLeadAdsBundle
Enables synchnronization of Facebook Lead Ads as leads into Mautic.

Development was sponsored by [Trinoco](https://www.trinoco.nl) for the [Qeado](https://www.qeado.com) project.

# Installation
1) Require the FB ads library in the Mautic root directory
composer require facebook/php-ads-sdk:2.9.*

The library has been tested on 5.6 to also work so if you get requirements errors try:
composer require --ignore-platform-reqs facebook/php-ads-sdk:2.9.*

2) Create a new Facebook App or use an existing one:
https://developers.facebook.com/apps/

3) Visit the app Dashboard page and store the app_id and app_key for later use.

4) Add the Marketing API Product.

5) Visit the Marketing API -> Tools page in your app and check the scopes and hit Get Token. Store this access_token for later use.

6) Go into your Facebook Ads account management and get your ad account id.

7) Enable the plugin and enter your app_id, app_secret, Marketing API access_token, ad account id and choose a verify token.

8) Authenticate the app using oAuth.

9) In the plugin settings map the fields to your contact lead fields.

10) Enable the webhooks Product in your Facebook App.

11) Go to the webhooks product in your Facebook APP and add your subscriber to the 'page' event:
url: $YOURHOST/plugin/fbadsleadads/leadform_subscriber
verify_token: Token you choose in step 7.

12) In your Facebook App Review add the manage_pages scope for review.
