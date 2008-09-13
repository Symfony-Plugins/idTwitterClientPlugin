<?php
/*
 * This file is part of the idTwitterClient package.
 * (c) 2008      Francesco Fullone <ff@ideato.it>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * idTwitterClient
 * this class expose all the twitter api in one place
 *
 *
 * @package    idTwitterClient
 * @author     Francesco Fullone <ff@ideato.it>
 * 
 */
class idTwitterClient extends sfWebBrowser {
  
  protected 
    $username   = null,
    $password   = null,
    $name       = 'idTwitterClient',
    $version    = '0.3.0',
    $url        = 'http://www.ideato.it',
    $length     = 140,
    $format     = 'json';  
    
  private $headers = array();
  
  private $availableFormat  = array(
                            'allowed' => array('json', 'xml', 'rss', 'atom'),
                            'search' => array('json', 'atom'),
  
                            'direct_message' => array('json', 'xml', 'rss', 'atom'),
                            'direct_message_sent' => array('json', 'xml' ),                            
                            'direct_message_create' => array('json', 'xml'),
                            'direct_message_destroy' => array('json', 'xml'),                            
                            
                            'friendship_destroy' => array('json', 'xml'),
                            'friendship_create' => array('json', 'xml'),
                            'friendship_exist' => array('json', 'xml'),
                            
                            'account_update_location' => array('json', 'xml'),
                            'account_update_delivery_device' => array('json', 'xml'),
                            'account_rate_limit_status' => array('json', 'xml'),
                            'account_verify_credentials' => array('json', 'xml'),
                            
                            'favorites' => array('json', 'xml', 'rss', 'atom'),
                            'favorites_create' => array('json', 'xml'),
                            'favorites_destroy' => array('json', 'xml'),
                            
                            'notification_follow' => array('json', 'xml'),
                            'notification_leave' => array('json', 'xml'),
                            
                            'blocks_create' => array('json', 'xml'),
                            'blocks_destroy' => array('json', 'xml'),                            
                            
                            'public_timeline' => array('json', 'xml', 'rss', 'atom'),
                            'friends_timeline' => array('json', 'xml', 'rss', 'atom'),
                            'user_timeline' => array('json', 'xml', 'rss', 'atom'),
                            
                            'status_show' => array('json', 'xml'),
                            'status_update' => array('json', 'xml'),
                            'status_destroy' => array('json', 'xml'),
                            'status_replies' => array('json', 'xml', 'rss', 'atom'),
                            
                            'user_friends' => array('json', 'xml'),
                            'user_followers' => array('json', 'xml'),
                            'user_featured' => array('json', 'xml'),
                            'user_show' => array('json', 'xml')
                            );
    
  /**
   * constructor method
   *
   * @param string $username twitter user screenname
   * @param string $password twitter user password
   * @param array $defaultHeaders
   * @param array $adapterClass
   * @param array $adapterOptions
   */
  public function __construct($username = null, $password = null, $defaultHeaders = array(), $adapterClass = null, $adapterOptions = array())   
  {
    if ($username AND $password)
    { 
      $this->setUsername($username);
      $this->setPassword($password);    
      $adapterClass = 'sfCurlAdapter';
      $adapterOptions += array('userpsw' => $username.':'.$password); 
    }
    
    $this->setHeaders( 
                       array(
                              'X-Twitter-Client' => $this->name,
                              'X-Twitter-Client-Version' => $this->version,
                              'X-Twitter-Client-URL' => $this->url
                             )
                     );
    
    $defaultHeaders += $this->headers;
       
    parent::__construct($defaultHeaders, $adapterClass, $adapterOptions);
  }  
  
  /**
   * The Search method allow to search on all the twitter basecode
   * 
   * query should be do like:
   * - twitter search                 containing both "twitter" and "search". This is the default operator.
   * - "happy hour"                   containing the exact phrase "happy hour".
   * - obama OR hillary               containing either "obama" or "hillary" (or both).
   * - beer -root                     containing "beer" but not "root".
   * - #haiku                         containing the hashtag "haiku".
   * - from:fullo                     sent from person "fullo".
   * - to:fullo	                      sent to person "fullo".
   * - @fullo	                        referencing person "fullo".
   * - "happy hour" near:"cesena"	    containing the exact phrase "happy hour" and sent near "cesena".
   * - near:NYC within:15mi	          sent within 15 miles of "NYC".
   * - superhero since:2008-05-01	    containing "superhero" and sent since date "2008-05-01" (year-month-day).
   * - ftw until:2008-05-03	          containing "ftw" and sent up to date "2008-05-03".
   * - movie -scary :)	              containing "movie", but not "scary", and with a positive attitude.
   * - flight :(	                    containing "flight" and with a negative attitude.
   * - traffic ?	                    containing "traffic" and asking a question.
   * - hilarious filter:links	        containing "hilarious" and linking to URLs.
   * 
   * parameters should be:
   * - q                              generic query, also setted by $query variable
   * - lang                           language based on ISO 639-1 code. ie lang=en
   * - rpp                            replies per page, max number is 100
   * - since_id                       returns tweets with status ids greater than the given id.
   * - geocode                        returns tweets by users located within a given radius of the given latitude/longitude, where the user's location is taken from their Twitter profile. The parameter value is specified by "latitide,longitude,radius", where radius units must be specified as either "mi" (miles) or "km" (kilometers). E.g., http://search.twitter.com/search.atom?geocode=40.757929%2C-73.985506%2C25km. Note that you cannot use the near operator via the API to geocode arbitrary locations; however you can use this geocode parameter to search near geocodes directly.
   * - show_user                      when "true", adds "<user>:" to the beginning of the tweet. This is useful for readers that do not display Atom's author field. The default is "false".
   *
   * @param string $query
   * @param array $parameter
   * @param string $format
   * @return Atom or Json search response
   */
  public function Search($query = '', $parameter = array(), $format = null)
  {
    $format = $this->getFormat($format);   
    
    if (!$this->checkFormat($format, 'search')) 
    { 
      throw new Exception('BlocksDestroy: allow '.$this->allowedFormat('search').' formats. Invalid format: '.$format);
    }    

    if ($query != '')
      $parameter += array( 'q' => $query);
    
    if (count($parameter)<1) 
    { 
      throw new Exception('Search: if no parameters is passed you have to specify at least the query parameter');
    }       
    
    return $this->twitterCall('http://search.twitter.com/search.'.$format, false, 'get', $parameter);
  }
  
  /**
   * Un-blocks the user specified in the ID parameter as the authenticating user.
   *
   * @param string $format 'json', 'xml'
   * @param int $user_id Required. The ID or screen_name of the user to un-block.  Ex: http://twitter.com/blocks/destroy/12345.json or http://twitter.com/blocks/destroy/bob.xml 
   * 
   * @return Returns the un-blocked user in the requested format when successful. 
   */
  public function BlocksDestroy($user_id, $format = null)
  {
    $format = $this->getFormat($format);   
    
    if (!$this->checkFormat($format, 'blocks_destroy')) 
    { 
      throw new Exception('BlocksDestroy: allow '.$this->allowedFormat('blocks_destroy').' formats. Invalid format: '.$format);
    }    
    
    return $this->twitterCall('http://twitter.com/blocks/destroy/'.$user_id.'.'.$format, true);
  }    
  
  /**
   * Blocks the user specified in the ID parameter as the authenticating user.   
   * You can find out more about blocking in the Twitter Support Knowledge Base. 
   *
   * @param string $format 'json', 'xml'
   * @param int $user_id Required. The ID or screen_name of the user to block.  Ex: http://twitter.com/blocks/create/12345.json or http://twitter.com/blocks/create/bob.xml
   * 
   * @return Returns the blocked user in the requested format when successful. 
   */
  public function BlocksCreate($user_id, $format = null)
  {
    $format = $this->getFormat($format);   
    
    if (!$this->checkFormat($format, 'blocks_create')) 
    { 
      throw new Exception('BlocksCreate: allow '.$this->allowedFormat('blocks_create').' formats. Invalid format: '.$format);
    }    
    
    return $this->twitterCall('http://twitter.com/blocks/create/'.$user_id.'.'.$format, true);
  }  
  
  /**
   * Disables notifications for updates from the specified user to the authenticating user.     
   *
   * @param string $format 'json', 'xml'
   * @param int $user_id Required.  The ID or screen name of the user to leave.  Ex:  http://twitter.com/notifications/leave/12345.xml or http://twitter.com/notifications/leave/bob.json 
   * 
   * @return Returns the specified user when successful. 
   */
  public function NotificationLeave($user_id, $format = null)
  {
    $format = $this->getFormat($format);   
    
    if (!$this->checkFormat($format, 'notification_leave')) 
    { 
      throw new Exception('NotificationLeave: allow '.$this->allowedFormat('notification_leave').' formats. Invalid format: '.$format);
    }    
    
    return $this->twitterCall('http://twitter.com/notifications/leave/'.$user_id.'.'.$format, true);
  }  

  /**
   * Enables notifications for updates from the specified user to the authenticating user.     
   *
   * @param string $format 'json', 'xml'
   * @param int $user_id Required.  The ID or screen name of the user to follow.  Ex:  http://twitter.com/notifications/follow/12345.xml or http://twitter.com/notifications/follow/bob.json 
   * 
   * @return Returns the specified user when successful. 
   */
  public function NotificationFollow($user_id, $format = null)
  {
    $format = $this->getFormat($format);   
    
    if (!$this->checkFormat($format, 'notification_follow')) 
    { 
      throw new Exception('NotificationFollow: allow '.$this->allowedFormat('notification_follow').' formats. Invalid format: '.$format);
    }    
    
    return $this->twitterCall('http://twitter.com/notifications/follow/'.$user_id.'.'.$format, true);
  }    

  /**
   * Un-favorites the status specified in the ID parameter as the authenticating user.    
   *
   * @param string $format 'json', 'xml'
   * @param int $status_id Required.  The ID of the status to un-favorite.  Ex: http://twitter.com/favorites/destroy/12345.json or http://twitter.com/favorites/destroy/23456.xml 
   * 
   * @return Returns the un-favorited status in the requested format when successful.
   */
  public function FavoritesDestroy($status_id, $format = null)
  {
    $format = $this->getFormat($format);   
    
    if (!$this->checkFormat($format, 'favorites_destroy')) 
    { 
      throw new Exception('FavoritesDestroy: allow '.$this->allowedFormat('favorites_destroy').' formats. Invalid format: '.$format);
    }    
    
    return $this->twitterCall('http://twitter.com/favorites/destroy/'.$status_id.'.'.$format, true);
  }    
  
  
  /**
   * Favorites the status specified in the ID parameter as the authenticating user.  Returns the favorite status when successful. 
   *
   * @param string $format 'json', 'xml'
   * @param int $status_id Required.  The ID of the status to favorite.  Ex: http://twitter.com/favorites/create/12345.json or http://twitter.com/favorites/create/45567.xml
   */
  public function FavoritesCreate($status_id, $format = null)
  {
    $format = $this->getFormat($format);   
    
    if (!$this->checkFormat($format, 'favorites_create')) 
    { 
      throw new Exception('FavoritesCreate: allow '.$this->allowedFormat('favorites_create').' formats. Invalid format: '.$format);
    }    
    
    return $this->twitterCall('http://twitter.com/favorites/create/'.$status_id.'.'.$format, true);
  }  
  
  /**
   * Returns the 20 most recent favorite statuses for the logged in user. 
   *
   * @param string $format 'json', 'xml', 'rss', 'atom'
   * @param int $page
   */
  public function Favorites($format = null, $page = 1)
  {
    $format = $this->getFormat($format);   
    
    if (!$this->checkFormat($format, 'favorites')) 
    { 
      throw new Exception('Favorites: allow '.$this->allowedFormat('favorites').' formats. Invalid format: '.$format);
    }    
    
    return $this->twitterCall('http://twitter.com/favorites.'.$format, true, 'get', array( 'page' => intval($page)));
  }
    
  /**
   * Returns the 20 most recent favorite statuses for a specified user by the ID parameter in the requested format. 
   *
   * @param string $user_id
   * @param string $format 'json', 'xml', 'rss', 'atom'
   * @param int $page
   */
  public function FavoritesByUser($user_id, $format = null, $page = 1)
  {
    $format = $this->getFormat($format);   
    
    if (!$this->checkFormat($format, 'favorites')) 
    { 
      throw new Exception('FavoritesByUser: allow '.$this->allowedFormat('favorites').' formats. Invalid format: '.$format);
    }    
    
    return $this->twitterCall('http://twitter.com/favorites/'.$user_id.'.'.$format, false, 'get', array( 'page' => intval($page)));
  }
  
  /**
   * Sets which device Twitter delivers updates to for the authenticating user.  
   * Sending none as the device parameter will disable IM or SMS updates.
   *
   * @param string $device  Required.  Must be one of: sms, im, none.  Ex: http://twitter.com/account/update_delivery_device?device=im
   * @param string $format 'json', 'xml'
   */
  public function AccountDeliveryDevice($device, $format = null)
  {
    $format = $this->getFormat($format);   
    
    if (!$this->checkFormat($format, 'account_update_delivery_device')) 
    { 
      throw new Exception('AccountDeliveryDevice: allow '.$this->allowedFormat('account_update_delivery_device').' formats. Invalid format: '.$format);
    }    

    if (!in_array($device, array('im', 'sms', 'none')))
    {
      throw new Exception('AccountDeliveryDevice: wrong device '.$device.' specified. Please be sure to use im, sms or none as value');
    }
    
    return $this->twitterCall('http://twitter.com/account/update_location.'.$format.'?location='.urlencode($location), true);
  }  
  
  /**
   * Returns an HTTP 200 OK response code and a format-specific response if authentication was successful.  
   * Use this method to test if supplied user credentials are valid with minimal overhead.
   * 
   * @param string $format 'json', 'xml'
   */
  public function AccountVerify($format = null)
  {
    $format = $this->getFormat($format);   
    
    if (!$this->checkFormat($format, 'account_verify_credentials')) 
    { 
      throw new Exception('AccountVerify: allow '.$this->allowedFormat('account_verify_credentials').' formats. Invalid format: '.$format);
    }    
    
    return $this->twitterCall('http://twitter.com/account/update_delivery_device.'.$format.'?device='.$device, true);
  }
    
  /**
   * Returns the remaining number of API requests available to the authenticating user before the API limit is reached for the current hour. 
   * Calls to rate_limit_status require authentication, but will not count against the rate limit. 
   *
   * @param string $format 'json', 'xml'
   */
  public function AccountRateLimit($format = null)
  {
    $format = $this->getFormat($format);   
    
    if (!$this->checkFormat($format, 'account_rate_limit_status')) 
    { 
      throw new Exception('AccountRateLimit: allow '.$this->allowedFormat('account_rate_limit_status').' formats. Invalid format: '.$format);
    }    
    
    return $this->twitterCall('http://twitter.com/account/rate_limit_status.'.$format, true);
  }
  
  /**
   * Updates the location attribute of the authenticating user, as displayed on the side of their profile and returned in various API methods.  
   * Works as either a POST or a GET.
   *
   * @param string $location Required.  The location of the user.  Please note this is not normalized, geocoded, or translated to latitude/longitude at this time.  Ex: http://twitter.com/account/update_location.xml?location=San%20Francisco
   * @param string $format 'json', 'xml'
   */
  public function AccountUpdateLocation($location, $format = null)
  {
    $format = $this->getFormat($format);   
    
    if (!$this->checkFormat($format, 'account_update_location')) 
    { 
      throw new Exception('AccountUpdateLocation: allow '.$this->allowedFormat('account_update_location').' formats. Invalid format: '.$format);
    }    
    
    return $this->twitterCall('http://twitter.com/account/update_location.'.$format.'?location='.urlencode($location), true);
  }
  
   /**
   * Returns extended information of a given user, specified by ID or screen name as per the required id parameter below.  
   * This information includes design settings, so third party developers can theme their widgets according to a given user's preferences. 
   * You must be properly authenticated to request the page of a protected user.
   * 
   * @param string $format 'json', 'xml'      
   * @param int $user_id Optional.  The ID or screen name of the user for whom to request a list of friends.  Ex: http://twitter.com/statuses/friends/12345.json or http://twitter.com/statuses/friends/bob.xml
   */
  public function UserShow($user_id, $format = null)
  {
    $format = $this->getFormat($format);   
    
    if (!$this->checkFormat($format, 'user_show')) 
    { 
      throw new Exception('UserShow: allow '.$this->allowedFormat('user_show').' formats. Invalid format: '.$format);
    }    
    
    return $this->twitterCall('http://twitter.com/users/show/'.$user_id.'.'.$format, true);
  }  
  
  /**
   * Returns extended information of a given user, specified by Email address.  
   * This information includes design settings, so third party developers can theme their widgets according to a given user's preferences. 
   * You must be properly authenticated to request the page of a protected user.
   * 
   * @param string $format 'json', 'xml'      
   * @param string $email Required.  The email address of a user.  Ex: http://twitter.com/users/show.xml?email=test@example.com
   */
  public function UserShowByEmail($email, $format = null)
  {
    $format = $this->getFormat($format);   
    
    if (!$this->checkFormat($format, 'user_show')) 
    { 
      throw new Exception('UserShowByEmail: allow '.$this->allowedFormat('user_show').' formats. Invalid format: '.$format);
    }    
    
    return $this->twitterCall('http://twitter.com/users/show.'.$format.'?email=/'.$email, true);
  }  
  
  
   /**
   * Returns a list of the users currently featured on the site with their current statuses inline. 
   *
   * @param string $format 'json', 'xml'      
   */
  public function UserFeatured($format = null)
  {
    $format = $this->getFormat($format);   
    
    if (!$this->checkFormat($format, 'user_featured')) 
    { 
      throw new Exception('UserFeatured: allow '.$this->allowedFormat('user_featured').' formats. Invalid format: '.$format);
    }    
    
    return $this->twitterCall('http://twitter.com/statuses/featured.'.$format, true);
  }  
  
   /**
   * Returns up to 100 of the authenticating user's friends who have most recently updated, each with current status inline. 
   * It's also possible to request another user's recent friends list via the $user_id parameter below. 
   *
   * @param string $format 'json', 'xml'      
   * @param int $page Optional. Retrieves the 20 next most recent direct messages.  Ex: http://twitter.com/direct_messages.xml?page=3
   * @param int $user_id Optional.  The ID or screen name of the user for whom to request a list of friends.  Ex: http://twitter.com/statuses/friends/12345.json or http://twitter.com/statuses/friends/bob.xml
   * @param boolean $lite  Optional.  Prevents the inline inclusion of current status.  Must be set to a value of true.  Ex: http://twitter.com/statuses/friends.xml?lite=true
   */
  public function UserFollowers($format = null, $user_id = null, $page = 1, $lite = false)
  {
    $format = $this->getFormat($format);   
    
    if (!$this->checkFormat($format, 'user_followers')) 
    { 
      throw new Exception('UserFollowers: allow '.$this->allowedFormat('user_followers').' formats. Invalid format: '.$format);
    }    
    
   $parameters =  array(
                'id' => $user_id,
                'page' => intval($page),
                'lite' => (boolean) $lite
              );     
    
    return $this->twitterCall('http://twitter.com/statuses/followers.'.$format, true, 'get', $parameters);
  }  
  
     /**
   * Returns up to 100 of the authenticating user's friends who have most recently updated, each with current status inline. 
   * It's also possible to request another user's recent friends list via the $user_id parameter below. 
   *
   * @param string $format 'json', 'xml'      
   * @param date $since Optional. Narrows the resulting list of direct messages to just those sent after the specified HTTP-formatted date.  The same behavior is available by setting the If-Modified-Since parameter in your HTTP request.  Ex: http://twitter.com/direct_messages.atom?since=Tue%2C+27+Mar+2007+22%3A55%3A48+GMT
   * @param int $page Optional. Retrieves the 20 next most recent direct messages.  Ex: http://twitter.com/direct_messages.xml?page=3
   * @param int $user_id Optional.  The ID or screen name of the user for whom to request a list of friends.  Ex: http://twitter.com/statuses/friends/12345.json or http://twitter.com/statuses/friends/bob.xml
   * @param boolean $lite  Optional.  Prevents the inline inclusion of current status.  Must be set to a value of true.  Ex: http://twitter.com/statuses/friends.xml?lite=true
   */
  public function UserFriends($format = null, $since = null, $user_id = null, $page = 1, $lite = false)
  {
    $format = $this->getFormat($format);   
    
    if (!$this->checkFormat($format, 'user_friends')) 
    { 
      throw new Exception('UserFriends: allow '.$this->allowedFormat('user_friends').' formats. Invalid format: '.$format);
    }    
    
   $parameters =  array(
                'since' => urlencode(date( 'r' ,strtotime($since))),
                'id' =>  $user_id,
                'page' => intval($page),
                'lite' => (boolean) $lite
              );     
    
    return $this->twitterCall('http://twitter.com/statuses/friends.'.$format, true, 'get', $parameters);
  }  
  
  
  /**
   * Returns the 20 most recent @replies (status updates prefixed with @username) for the authenticating user.
   *
   * @param string $format 'json', 'xml', 'rss', 'atom'    
   * @param date $since Optional. Narrows the resulting list of direct messages to just those sent after the specified HTTP-formatted date.  The same behavior is available by setting the If-Modified-Since parameter in your HTTP request.  Ex: http://twitter.com/direct_messages.atom?since=Tue%2C+27+Mar+2007+22%3A55%3A48+GMT
   * @param int  $since_id Optional. Returns only direct messages with an ID greater than (that is, more recent than) the specified ID.  Ex: http://twitter.com/direct_messages.xml?since_id=12345
   * @param int $page Optional. Retrieves the 20 next most recent direct messages.  Ex: http://twitter.com/direct_messages.xml?page=3
   * 
   */
  public function StatusReplies($format = null, $since = null, $since_id = null, $page = 1)
  {
    $format = $this->getFormat($format);   
    
    if (!$this->checkFormat($format, 'status_replies')) 
    { 
      throw new Exception('StatusReplies: allow '.$this->allowedFormat('status_replies').' formats. Invalid format: '.$format);
    }    
    
    $parameters =  array(
                'since' => urlencode(date( 'r' ,strtotime($since))),
                'since_id' => intval($since_id),
                'page' => intval($page)
              );     
              
    return $this->twitterCall('http://twitter.com/statuses/replies.'.$format, true, 'get', $parameters);
  }
  
  /**
   * Destroys the status specified by the required ID parameter.  
   * The authenticating user must be the author of the specified status. 
   *
   * @param string $format 'json', 'xml'   
   * @param int $status_id  Required.  The ID of the status to destroy.  Ex: http://twitter.com/statuses/destroy/12345.json or http://twitter.com/statuses/destroy/23456.xml 
   * 
   */  
  public function StatusDestroy($status_id, $format = null)
  {
    $format = $this->getFormat($format);   
    
    if (!$this->checkFormat($format, 'status_destroy')) 
    { 
      throw new Exception('setStatusDestroy: allow '.$this->allowedFormat('status_destroy').' formats. Invalid format: '.$format);
    }   
    
    return $this->twitterCall('http://twitter.com/statuses/destroy/'.intval($status_id).'.'.$format, true);
  }
  
  
  /**
   * Updates the authenticating user's status.  
   * Requires the status parameter specified below. 
   *
   * @param string $format 'json', 'xml'   
   * @param int $status  Required.  The text of your status update.  Be sure to URL encode as necessary.  Must not be more than 160 characters and should not be more than 140 characters to ensure optimal display.  
   * 
   * @return Returns the posted status in requested format when successful.
   */
  public function StatusUpdate($status, $format = null)
  {    
    $format = $this->getFormat($format);   
    
    if (!$this->checkFormat($format, 'status_update')) 
    { 
      throw new Exception('setStatusUpdate: allow '.$this->allowedFormat('status_update').' formats. Invalid format: '.$format);
    }       
    
    return $this->twitterCall('http://twitter.com/statuses/update.'.$format, true, 'post', array( 'status' => substr($status, 0, $this->length)));
  }
  
  
  /**
   * Returns a single status, specified by the id parameter below.  
   * The status's author will be returned inline.
   *
   * @param string $format 'json', 'xml'    
   * @param int $status_id  Required. The numerical ID of the status you're trying to retrieve.  Ex: http://twitter.com/statuses/show/123.xml    
   * 
   */
  public function StatusId($status_id, $format = null)
  {
    $format = $this->getFormat($format);   
    
    if (!$this->checkFormat($format, 'status_show')) 
    { 
      throw new Exception('StatusId: allow '.$this->allowedFormat('status_show').' formats. Invalid format: '.$format);
    }    
    
    return $this->twitterCall('http://twitter.com/statuses/show/'.intval($status_id).'.'.$format, true);
  }
  
  
  
  /**
   * Returns the 20 most recent statuses posted from the authenticating user. 
   * It's also possible to request another user's timeline via the id parameter below. 
   * This is the equivalent of the Web /archive page for your own user, or the profile page for a third party.
   *
   * @param string $format 'json', 'xml', 'rss', 'atom'       
   * @param date $since Optional. Narrows the resulting list of direct messages to just those sent after the specified HTTP-formatted date.  The same behavior is available by setting the If-Modified-Since parameter in your HTTP request.  Ex: http://twitter.com/direct_messages.atom?since=Tue%2C+27+Mar+2007+22%3A55%3A48+GMT
   * @param int  $since_id Optional. Returns only direct messages with an ID greater than (that is, more recent than) the specified ID.  Ex: http://twitter.com/direct_messages.xml?since_id=12345
   * @param int $page Optional. Retrieves the 20 next most recent direct messages.  Ex: http://twitter.com/direct_messages.xml?page=3
   * @param int $count Optional.  Specifies the number of statuses to retrieve. May not be greater than 200.  Ex: http://twitter.com/statuses/friends_timeline.xml?count=5 
   */
  public function UserTimeline($format = null, $since = null, $since_id = null, $page = 1, $count = 20)
  {
    $format = $this->getFormat($format);   
    
    if (!$this->checkFormat($format, 'user_timeline')) 
    { 
      throw new Exception('UserTimeline: allow '.$this->allowedFormat('user_timeline').' formats. Invalid format: '.$format);
    }    
    
   $parameters =  array(
                'since' => urlencode(date( 'r' ,strtotime($since))),
                'since_id' => intval($since_id),
                'page' => intval($page),
                'count' => intval($count)   
              );     
    
    return $this->twitterCall('http://twitter.com/statuses/user_timeline.'.$format, true, 'get', $parameters);
  }  
  
  
  /**
   * Returns the 20 most recent statuses posted by the authenticating user and that user's friends. 
   * This is the equivalent of /home on the Web. 
   *
   * @param string $format 'json', 'xml', 'rss', 'atom'      
   * @param date $since Optional. Narrows the resulting list of direct messages to just those sent after the specified HTTP-formatted date.  The same behavior is available by setting the If-Modified-Since parameter in your HTTP request.  Ex: http://twitter.com/direct_messages.atom?since=Tue%2C+27+Mar+2007+22%3A55%3A48+GMT
   * @param int  $since_id Optional. Returns only direct messages with an ID greater than (that is, more recent than) the specified ID.  Ex: http://twitter.com/direct_messages.xml?since_id=12345
   * @param int $page Optional. Retrieves the 20 next most recent direct messages.  Ex: http://twitter.com/direct_messages.xml?page=3
   * @param int $count Optional.  Specifies the number of statuses to retrieve. May not be greater than 200.  Ex: http://twitter.com/statuses/friends_timeline.xml?count=5 
   */
  public function FriendsTimeline($format = null, $since = null, $since_id = null, $page = 1, $count = 20)
  {
    $format = $this->getFormat($format);   
    
    if (!$this->checkFormat($format, 'friends_timeline')) 
    { 
      throw new Exception('FriendsTimeline: allow '.$this->allowedFormat('friends_timeline').' formats. Invalid format: '.$format);
    }    
    
   $parameters =  array(
                'since' => urlencode(date( 'r' ,strtotime($since))),
                'since_id' => intval($since_id),
                'page' => intval($page),
                'count' => intval($count)   
              );     
    
    return $this->twitterCall('http://twitter.com/statuses/friends_timeline.'.$format, true, 'get', $parameters);
  }
  
  /**
   * Returns the 20 most recent statuses from non-protected users who have set a custom user icon.  
   * Does not require authentication.
   *
   * @param string $format  'json', 'xml', 'rss', 'atom'  
   * 
   */
  public function PublicTimeline($format = null)
  {
    $format = $this->getFormat($format);   
    
    if (!$this->checkFormat($format, 'public_timeline')) 
    { 
      throw new Exception('PublicTimeline: allow '.$this->allowedFormat('public_timeline').' formats. Invalid format: '.$format);
    }    
    
    return $this->twitterCall('http://twitter.com/statuses/public_timeline.'.$format, false);
  }
  
  
  /**
   * Returns a list of the 20 most recent direct messages sent to the authenticating user.  
   * The XML and JSON versions include detailed information about the sending and recipient users.
   *
   * @param string $format 'json', 'xml', 'rss', 'atom'
   * @param date $since Optional. Narrows the resulting list of direct messages to just those sent after the specified HTTP-formatted date.  The same behavior is available by setting the If-Modified-Since parameter in your HTTP request.  Ex: http://twitter.com/direct_messages.atom?since=Tue%2C+27+Mar+2007+22%3A55%3A48+GMT
   * @param int  $since_id Optional. Returns only direct messages with an ID greater than (that is, more recent than) the specified ID.  Ex: http://twitter.com/direct_messages.xml?since_id=12345
   * @param int $page Optional. Retrieves the 20 next most recent direct messages.  Ex: http://twitter.com/direct_messages.xml?page=3
   * 
   */
  public function DirectMessages($format = null, $since = null, $since_id = null, $page = 1)
  {
    $format = $this->getFormat($format);   
    
    if (!$this->checkFormat($format, 'direct_message')) 
    { 
      throw new Exception('DirectMessages: allow '.$this->allowedFormat('direct_message').' formats. Invalid format: '.$format);
    }    

   $parameters =  array(
                    'since' => ($since)?urlencode(date( 'r' ,strtotime($since))):null,
                    'since_id' => intval($since_id)?intval($since_id):null,
                    'page' => intval($page)   
                  );     
    
    return $this->twitterCall('http://twitter.com/direct_messages.'.$format, true, 'get', $parameters);
  }
  
  /**
   * Returns a list of the 20 most recent direct messages sent by the authenticating user.  
   * The XML and JSON versions include detailed information about the sending and recipient users.
   *
   * @param string $format 'json', 'xml'
   * @param date $since Optional. Narrows the resulting list of direct messages to just those sent after the specified HTTP-formatted date.  The same behavior is available by setting the If-Modified-Since parameter in your HTTP request.  Ex: http://twitter.com/direct_messages.atom?since=Tue%2C+27+Mar+2007+22%3A55%3A48+GMT
   * @param int  $since_id Optional. Returns only direct messages with an ID greater than (that is, more recent than) the specified ID.  Ex: http://twitter.com/direct_messages.xml?since_id=12345
   * @param int $page Optional. Retrieves the 20 next most recent direct messages.  Ex: http://twitter.com/direct_messages.xml?page=3
   */
  public function DirectMessagesSent($format = null, $since = null, $since_id = null, $page = 1)
  {   
    $format = $this->getFormat($format);   
     
    if (!$this->checkFormat($format, 'direct_message_sent')) 
    { 
      throw new Exception('DirectMessagesSent: allow '.$this->allowedFormat('direct_message_sent').' formats. Invalid format: '.$format);
    }
   
   $parameters =  array(
                    'since' => urlencode(date( 'r' ,strtotime($since))),
                    'since_id' => intval($since_id),
                    'page' => intval($page)   
                  ); 
    
   return $this->twitterCall('http://twitter.com/direct_messages/sent.'.$format, true, 'get', $parameters);      
  }
  
  /**
   * Sends a new direct message to the specified user from the authenticating user.  Requires both the user and text parameters below.  
   * Request must be a POST.  Returns the sent message in the requested format when successful. 
   *
   * @param string $user    Required.  The ID or screen name of the recipient user.
   * @param string $text    Required.  The text of your direct message.  Be sure to URL encode as necessary, and keep it under 140 characters.  
   * @param string $format  'json', 'xml'
   * 
   * @return Returns the sent message in the requested format when successful. 
   */
  public function DirectMessageCreate($user, $text, $format = null)
  {  
    $format = $this->getFormat($format);   
      
    if (!$this->checkFormat($format, 'direct_message_create'))
    { 
      throw new Exception('DirectMessageCreate: allow '.$this->allowedFormat('direct_message_sent').' formats. Invalid format: '.$format);
    }
    
   return $this->twitterCall('http://twitter.com/direct_messages/new.'.$format, true, 'post', array('user' => $user, 'text' => $text));
  }  

  
  /**
   * Destroys the direct message specified in the required ID parameter.  
   * The authenticating user must be the recipient of the specified direct message.
   *
   * @param string $message_id   Required.  The ID of the direct message to destroy
   * @param string $format 'json', 'xml'
   */
  public function DirectMessagesDestroy($message_id, $format = null)
  {    
    $format = $this->getFormat($format);   
    
    if (!$this->checkFormat($format, 'direct_message_destroy'))
    { 
      throw new Exception('DirectMessagesDestroy: allow '.$this->allowedFormat('direct_message_destroy').' formats. Invalid format: '.$format);
    }
    
   return $this->twitterCall('http://twitter.com/direct_messages/destroy/'.$message_id.'.'.$format, true);
  }  
  
  /**
   * Befriends the user specified in the ID parameter as the authenticating user.  
   * Returns the befriended user in the requested format when successful.  
   * Returns a string describing the failure condition when unsuccessful.
   *
   * @param mixed $friend Required.  The ID or screen name of the user to befriend.  Ex: http://twitter.com/friendships/create/12345.json or http://twitter.com/friendships/create/bob.xml
   * @param string $format 'json', 'xml'
   * 
   * @return Returns the befriended user in the requested format when successful. Returns a string describing the failure condition when unsuccessful.
   */
  public function FriendshipCreate($friend, $format = null)
  {
    $format = $this->getFormat($format);   
    
    if (!$this->checkFormat($format, 'friendship_create'))
    { 
      throw new Exception('FriendshipCreate: allow '.$this->allowedFormat('friendship_create').' formats. Invalid format: '.$format);
    }
    
    return $this->twitterCall('http://twitter.com/friendships/create/'.$friend.'.'.$format, true);     
  }
  
  /**
   * Discontinues friendship with the user specified in the ID parameter as the authenticating user.  
   * Returns the un-friended user in the requested format when successful.  
   * Returns a string describing the failure condition when unsuccessful. 
   *
   * @param mixed $friend Required.  The ID or screen name of the user to befriend.  Ex: http://twitter.com/friendships/destroy/12345.json or http://twitter.com/friendships/destroy/bob.xml
   * @param string $format 'json', 'xml'
   * 
   * @return Returns the un-friended user in the requested format when successful.  Returns a string describing the failure condition when unsuccessful. 
   */
  public function FriendshipDestroy($friend, $format = null)
  {
    $format = $this->getFormat($format);   
    
    if (!$this->checkFormat($format, 'friendship_destroy'))
    { 
      throw new Exception('FriendshipDestroy: allow '.$this->allowedFormat('friendship_destroy').' formats. Invalid format: '.$format);
    }
    
    return $this->twitterCall('http://twitter.com/friendships/destroy/'.$friend.'.'.$format, true);     
  }  
  
  /**
   * Tests if a friendship exists between two users.
   * Returns the un-friended user in the requested format when successful.  
   * Returns a string describing the failure condition when unsuccessful. 
   *
   * @param mixed $user_a Required.  The ID or screen_name of the first user to test friendship for.
   * @param mixed $user_b Required.  The ID or screen_name of the second user to test friendship for.
   * @param string $format 'json', 'xml'
   * 
   * @return Returns the un-friended user in the requested format when successful. Returns a string describing the failure condition when unsuccessful. 
   */
  public function FriendshipExist($user_a, $user_b, $format = null)
  {
    $format = $this->getFormat($format);   
    
    if (!$this->checkFormat($format, 'friendship_exist'))
    { 
      throw new Exception('getFriendshipExist: allow '.$this->allowedFormat('friendship_exist').' formats. Invalid format: '.$format);
    }
    
    return $this->twitterCall('http://twitter.com/friendships/exists.'.$format.'?user_a='.urlencode($user_a).'&user_b='.urlencode($user_b), true);     
  }  
  
  
/**
 * API call by Call method
 *
 *
 * @param string $uri
 * @param boolean $auth
 * @param string $method
 * @param array $parameter
 * @param array $headers
 * @return SimpleXMLElement or String with the response contents
 */
  private function twitterCall($uri, $auth = false, $method = 'get', $parameter = array(), $headers = array())
  {
    if (!in_array($method, array('get', 'post', 'put')))
    {
      throw new Exception('twitterCall: method allowed are get, post and put. Invalid method: '.$method);
    }
    
    if ($auth)
    {
      if (is_null($this->password) OR is_null($this->username))
      {
        throw new Exception('twitterCall: the uri '.$uri.' need authentication.');
      }
    }
    
    $headers = $this->getHeaders($headers);
    $this->setUserAgent($this->name.' v.'.$this->version);
        
    if($this->$method($uri, $parameter, $headers)->responseIsError())
    {
      $error = 'The given URL (%s) returns an error (%s: %s)';
      $error = sprintf($error, $uri, $this->getResponseCode(), $this->getResponseMessage());
      throw new Exception($error);
    }
    
    if (eregi('.xml', $uri))
      return $this->getResponseXML(); 
    
    if (eregi('.rss', $uri) AND class_exists('sfFeedPeer'))
      return sfFeedPeer::createFromXml($this->getResponseText(), $uri);

    if (eregi('.atom', $uri) AND class_exists('sfFeedPeer'))
      return sfFeedPeer::createFromXml($this->getResponseText(), $uri);      
      
    return $this->getResponseText(); 
  }
  
  /**
   * get the headers merged with the default
   *
   * @param array $headers
   * @return array
   */
  public function getHeaders($headers = array())
  {
    return array_merge($headers, $this->headers);
  }
  
  /**
   * Set the default headers used by the application
   *
   * @param array $headers
   */
  public function setHeaders($headers = array())
  {
    if (is_array($headers))
      $this->headers = $headers;
  }
  
  
  /**
   * set the twitter username
   *
   * @param string $username
   */
  public function setUsername($username = null)
  {
    $this->username = $username;
  }    

  /**
   * set the twitter password
   *
   * @param string $password
   */  
  public function setPassword($password = null)
  {
    $this->password = $password;
  }
  
  /**
   * set the default format for output
   *
   * @param string $format
   */
  public function setDefaultFormat($format)
  {
    if (!$this->checkFormat($format, 'allowed')) 
    { 
      throw new Exception('setDefaultFormat: you can only use one of this formats: '.$this->allowedFormat('allowed').' formats. Invalid format: '.$format);
    } 
        
    $this->format = $format;
  }

  /**
   * Check if the gived format il allowed by the method
   *
   * @param string $format
   * @param string $method
   * @return boolean
   */
  private function checkFormat($format, $method)
  {
    
    $format = $this->getFormat($format);    
    
    if (in_array($format, $this->availableFormat[$method]))
      return true;
        
    return false;
  }
  
  /**
   * give the list of the allowed format for a method
   *
   * @param string $method
   * @return string allowed output format
   */
  private function allowedFormat($method)
  {
    return implode(', ', $this->availableFormat[$method]);
  }  
  
  /**
   * If the gived format is null then return the default format
   *
   * @param string $format
   * @return default format
   */
  private function getFormat($format = null)
  {    
    if (!is_null($format) AND $format != '')
      return $format;
      
    return $this->format; 
  }
  
  /**
   * set the maximum lenght of a message
   *
   * @param int $length
   */
  public function setLength($length = 140)
  {
    if (is_int($length))
      $this->length = intval($length);
  }
}
