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

namespace InsiteApps\SocialFeeds\Providers;

use \League\OAuth2\Client\Provider\Facebook;
use LiteralField;
use DropdownField;
use RequiredFields;
use Exception;
use DBField;

class SocialFeedFacebook extends SocialFeed implements ProviderInterface
{
    const POSTS_AND_COMMENTS = 0;
    const POSTS_ONLY = 1;
    private static $table_name = "SocialFeedFacebook";
    private static $db = array(
        'PageID'      => 'Varchar(100)',
        'AppID'       => 'Varchar(400)',
        'AppSecret'   => 'Varchar(400)',
        'AccessToken' => 'Varchar(400)',
        'Type'        => 'Int',
    );

    private static $singular_name = 'Facebook';
    private static $plural_name = 'Facebook';

    private static $summary_fields = array(
        'Label',
        'Enabled',
        'PageID',
    );


    private static $facebook_types = array(
        self::POSTS_AND_COMMENTS => 'Page Posts and Comments',
        self::POSTS_ONLY         => 'Page Posts Only',
    );

    private $type = 'facebook';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldsToTab('Root.Main', new LiteralField('sf_html_1', '<h4>To get the necessary Facebook API credentials you\'ll need to create a <a href="https://developers.facebook.com/apps" target="_blank">Facebook App.</a></h4><p>&nbsp;</p>'), 'Label');
        $fields->replaceField('Type', DropdownField::create('FacebookType', 'Facebook Type', $this->config()->facebook_types));
        $fields->removeByName('AccessToken');

        return $fields;
    }

    public function getCMSValidator()
    {
        return new RequiredFields(array('PageID', 'AppID', 'AppSecret'));
    }

    public function onBeforeWrite()
    {
        if ($this->AppID && $this->AppSecret) {
            $this->AccessToken = $this->AppID . '|' . $this->AppSecret;
        } else if ($this->AccessToken) {
            $this->AccessToken = '';
        }

        parent::onBeforeWrite();
    }

    /**
     * Return the type of provider
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    public function getFeedUncached()
    {
        $accessToken = $this->AppID . '|' . $this->AppSecret;
        $provider = new Facebook([
            'clientId'        => $this->AppID,
            'clientSecret'    => $this->AppSecret,
            // https://github.com/thephpleague/oauth2-facebook#graph-api-version
            'graphApiVersion' => 'v2.11',
        ]);

        // For an App Access Token we can just use our App ID and App Secret pipped together
        // https://developers.facebook.com/docs/facebook-login/access-tokens#apptokens
        //$accessToken = $this->AccessToken;
//\Debug::show($this->AccessToken);
        // Setup query params for FB query
        $queryParameters = array(
            // Get Facebook timestamps in Unix timestamp format
            'date_format'  => 'U',
            // Explicitly supply all known 'fields' as the API was returning a minimal fieldset by default.
            'fields'       => 'from,message,message_tags,story,story_tags,full_picture,source,link,object_id,name,caption,description,icon,privacy,type,status_type,created_time,updated_time,shares,is_hidden,is_expired,likes,comments',
            'access_token' => $accessToken,
        );
        $queryParameters = http_build_query($queryParameters);

        // Get all data for the FB page
        switch ($this->Type) {
            case self::POSTS_AND_COMMENTS:
                $request = $provider->getRequest('GET', 'https://graph.facebook.com/' . $this->PageID . '/feed?' . $queryParameters);
                break;

            case self::POSTS_ONLY:
                $request = $provider->getRequest('GET', 'https://graph.facebook.com/' . $this->PageID . '/posts?' . $queryParameters);
                break;

            default:
                throw new Exception('Invalid FacebookType (' . $this->Type . ')');
                break;
        }
        $result = $provider->getResponse($request);

        return $result['data'];
    }

    /**
     * @return HTMLText
     */
    public function getPostContent($post)
    {
        $text = isset($post['message']) ? $post['message'] : '';
        $result = DBField::create_field('HTMLText', $text);

        return $result;
    }

    /**
     * Get the creation time from a post
     *
     * @param $post
     *
     * @return mixed
     */
    public function getPostCreated($post)
    {
        return $post['created_time'];
    }

    /**
     * Get the post URL from a post
     *
     * @param $post
     *
     * @return mixed
     */
    public function getPostUrl($post)
    {
        if (isset($post['actions'][0]['name']) && $post['actions'][0]['name'] === 'Share') {
            return $post['actions'][0]['link'];
        } else if (isset($post['link']) && $post['link']) {
            // For $post['type'] === 'link' && $post['status_type'] === 'shared_story'
            return $post['link'];
        }

        return null;
    }

    /**
     * Get the user who made the post
     *
     * @param $post
     *
     * @return mixed
     */
    public function getUserName($post)
    {
        return $post['from']['name'];
    }

    /**
     * Get the primary image for the post
     *
     * @param $post
     *
     * @return mixed
     */
    public function getImage($post)
    {
        return (isset($post['full_picture'])) ? $post['full_picture'] : false;
    }
}
