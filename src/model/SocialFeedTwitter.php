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

use \Abraham\TwitterOAuth\TwitterOAuth;
use LiteralField;
use DropdownField;
use RequiredFields;
use Exception;
use DBField;

class SocialFeedTwitter extends SocialFeed implements ProviderInterface
{
    private static $db = array(
        'ConsumerKey'       => 'Varchar(400)',
        'ConsumerSecret'    => 'Varchar(400)',
        'AccessToken'       => 'Varchar(400)',
        'AccessTokenSecret' => 'Varchar(400)',
        'ScreenName'        => 'Varchar',
    );

    private static $singular_name = 'Twitter';
    private static $plural_name = 'Twitter';

    private $type = 'twitter';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldsToTab('Root.Main', new LiteralField('sf_html_1', '<h4>To get the necessary Twitter API credentials you\'ll need to create a <a href="https://apps.twitter.com" target="_blank">Twitter App.</a></h4>'), 'Label');
        $fields->addFieldsToTab('Root.Main', new LiteralField('sf_html_2', '<p>You can manually grant permissions to the Twitter App, this will give you an Access Token and Access Token Secret.</h5><p>&nbsp;</p>'), 'Label');

        return $fields;
    }

    public function getCMSValidator()
    {
        return new RequiredFields(array('ConsumerKey', 'ConsumerSecret'));
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
        // NOTE: Twitter doesn't implement OAuth 2 so we can't use https://github.com/thephpleague/oauth2-client
        $connection = new TwitterOAuth($this->ConsumerKey, $this->ConsumerSecret, $this->AccessToken, $this->AccessTokenSecret);
        $parameters = ['count' => 25, 'exclude_replies' => true];
        if ($this->ScreenName) {
            $parameters['screen_name'] = $this->ScreenName;
        }
        $result = $connection->get('statuses/user_timeline', $parameters);
        if (isset($result->error)) {
            user_error($result->error, E_USER_WARNING);
        }

        return $result;
    }

    /**
     * @return HTMLText
     */
    public function getPostContent($post)
    {
        $text = isset($post->text) ? $post->text : '';
        $text = preg_replace('/(https?:\/\/[a-z0-9\.\/]+)/i', '<a href="$1" target="_blank">$1</a>', $text);

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
        return $post->created_at;
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
        return 'https://twitter.com/' . (string)$post->user->id . '/status/' . (string)$post->id;
    }

    /**
     * The user's name who tweeted
     *
     * @param $post
     *
     * @return mixed
     */
    public function getUserName($post)
    {
        return $post->user->name;
    }

    /**
     * The first image for a Tweet
     *
     * @param $post
     *
     * @return mixed
     */
    public function getImage($post)
    {
        if (property_exists($post->entities, 'media') && $post->entities->media[0]->media_url_https) {
            return $post->entities->media[0]->media_url_https;
        }
    }
}
