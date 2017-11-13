<?php
/**
 *
 * @copyright (c) 2017 Insite Apps - http://www.insiteapps.co.za
 * @package       insiteapps
 * @author        Patrick Chitovoro  <patrick@insiteapps.co.za>
 * All rights reserved. No warranty, explicit or implicit, provided.
 *
 * NOTICE:  All information contained herein is, and remains the property of Insite Apps and its suppliers,  if any.
 * The intellectual and technical concepts contained herein are proprietary to Insite Apps and its suppliers and may be
 * covered by South African. and Foreign Patents, patents in process, and are protected by trade secret or copyright
 * laws. Dissemination of this information or reproduction of this material is strictly forbidden unless prior written
 * permission is obtained from Insite Apps. Proprietary and confidential. There is no freedom to use, share or change
 * this file.
 *
 *
 */

namespace InsiteApps\SocialFeeds;

use InsiteApps\SocialFeeds\Providers\SocialFeedFacebook;
use InsiteApps\SocialFeeds\Providers\SocialFeedTwitter;
use ModelAdmin;

class SocialFeedAdmin extends ModelAdmin
{
    private static $managed_models = array(
        "InsiteApps\SocialFeeds\Providers\SocialFeedFacebook",
        "InsiteApps\SocialFeeds\Providers\SocialFeedTwitter",
        "InsiteApps\SocialFeeds\Providers\SocialFeedInstagram",
        
    );

    private static $url_segment = 'social-feeds';

    private static $menu_title = 'Social Feeds';

    public function init()
    {
        parent::init();

        // get the currently managed model
        $model = $this->getRequest()->param('ModelClass');

        // Instagram OAuth flow in action
        if ($model === 'SocialFeedProviderInstagram' && isset($_GET['provider_id']) && is_numeric($_GET['provider_id']) && isset($_GET['code'])) {
            // Find provider
            $instagramProvider = DataObject::get_by_id('SocialFeedProviderInstagram', $_GET['provider_id']);

            // Fetch access token using code
            $accessToken = $instagramProvider->fetchAccessToken($_GET['code']);

            // Set and save access token
            $instagramProvider->AccessToken = $accessToken->getToken();
            $instagramProvider->write();

            // Send user back to edit page
            // TODO: show user a notification?
            header('Location: ' . Director::absoluteBaseURL() . 'admin/social-feed/' . $model . '/EditForm/field/' . $model . '/item/' . $_GET['provider_id'] . '/edit');
            exit;
        }
    }
}
