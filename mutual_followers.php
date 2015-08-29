<?php

require 'twitter-api-php/TwitterAPIExchange.php';
require 'settings.php';


class mutualFollowers extends stdClass {

  private $settings = array();

  function __construct($settings) {
    $this->settings = $settings;
  }

  public function getFollowers($screen_name) {
    $twitter = new TwitterAPIExchange($this->settings);
    $url = 'https://api.twitter.com/1.1/followers/ids.json';
    $requestMethod = 'GET';

    $getfield = array(
      'screen_name' => $screen_name,
      'cursor' => -1,
    );

    $followers = array();
    while ($getfield['cursor'] != 0) {
      $response = $twitter->setGetfield($getfield)
        ->buildOauth($url, $requestMethod)
        ->performRequest();
      $followers = array_merge($followers, $response['ids']);
      $getfield['cursor'] = $response['next_cursor'];
    }
      
    return $followers;
  }

  public function compareFollowers($screen_name1, $screen_name2) {
    $followers1 = $this->getFollowers($screen_name1);
    $followers2 = $this->getFollowers($screen_name2);

    return array_intersect($followers1, $follwers2);
  }

}

$mutual = new mutualFollowers($settings);
$followers = $mutual->compareFollowers('velocityconf', 'oreillymedia');
return count($followers);
