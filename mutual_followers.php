<?php

require 'twitter-api-php/TwitterAPIExchange.php';
require 'settings.php';


class mutualFollowers extends stdClass {

  private $settings = array();

  private $twFollowers = array(
    'method' => 'GET',
    'url' => 'https://api.twitter.com/1.1/followers/ids.json',
    'resource' => 'followers',
  );

  private $twRate = array(
    'method' => 'GET',
    'url' => 'https://api.twitter.com/1.1/application/rate_limit_status.json',
    'resource' => 'application',
  );

  function __construct($settings) {
    $this->settings = $settings;
  }

  public function getFollowers($screen_name) {
    $twitter = new TwitterAPIExchange($this->settings);

    $getfield = array(
      'screen_name' => $screen_name,
      'cursor' => -1,
    );
    $followers = array();

    while ($getfield['cursor'] != 0) {
      // Check for when we can get the next set of followers.
      $response = $twitter->setGetfield('resources=' . $this->twFollowers['resource'])
        ->buildOauth($this->twRate['url'], $this->twRate['method'])
        ->performRequest();
      $rate = json_decode($response);

      // If we are close to the limit, wait until the reset time.
      if ($rate->resources->followers->{'/followers/ids'}->remaining <= 2) {
        $sleepTime = $rate->resources->followers->{'/followers/ids'}->reset - time();
        echo 'Nearing rate limit, waiting ' . $sleepTime . " seconds.\n";
        sleep($sleepTime);
      }
      
      // Request the set of followers.
      $response = $twitter->setGetfield(http_build_query($getfield))
        ->buildOauth($this->twFollowers['url'], $this->twFollowers['method'])
        ->performRequest();
      $json = json_decode($response);

      // Return if there are errors.
      if (!empty($json->errors)) {
        foreach ($json->errors as $error) {
          echo "$error->message\n";
        }
       return;
      }

      // Add followers to results and iterate cursor.
      echo 'Adding ' . count($json->ids) . ' followers to ' . $screen_name . "\n";
      $followers = array_merge($followers, $json->ids);
      $getfield['cursor'] = $json->next_cursor;
    }
      
    return $followers;
  }

  public function compareFollowers($screen_name1, $screen_name2) {
    $followers1 = $this->getFollowers($screen_name1);
    $followers2 = $this->getFollowers($screen_name2);

    if (!is_array($followers1) || !is_array($followers2)) {
      echo "Non-array returned for followers list.\n";
      return;
    }

    return array_intersect($followers1, $followers2);
  }

}

$mutual = new mutualFollowers($settings);
$followers = $mutual->compareFollowers('velocityconf', 'oreillymedia');
echo 'Mutual followers: ' . count($followers) . "\n";
