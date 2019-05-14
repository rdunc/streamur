<?php 

namespace App\Classes;
 
use App\Models\TwitchChannel;
use App\Models\TwitchStream;
use App\Classes\TwitchData;
use App\Models\Emoticon;
use App\Models\Site;
use Carbon;

class Twitch
{

	private $twitchUrl 				= "https://api.twitch.tv/kraken";
	private $twitchUrl2				= "https://api.twitch.tv/api";
	private $channelsEndpoint 		= "/channels";
	private $streamsEndpoint 		= "/streams";
	private $panelsEndpoint			= "/panels";
	private $usersEndpoint			= "/users";
	private $chatEndpoint			= "/chat";

	private $name;

	/**
	* Create a new twitch class instance.
	*
	* @return void
	*/
	public function __construct($name)
	{
		$this->name = $name;
	}

	/**
     * Return twitch user object
     *
     * @return User
     */
	public function getUser()
	{
		$user = $this->getData($this->twitchUrl.$this->usersEndpoint."/".$this->name);
		return $user;
	}

	/**
     * Return twitch channel object
     *
     * @return Channel
     */
	public function getChannel()
	{
		$channel = $this->getData($this->twitchUrl.$this->channelsEndpoint."/".$this->name);
		return $channel;
	}

	/**
     * Return twitch stream object
     *
     * @return Stream
     */
	public function getStream()
	{
		$stream = $this->getData($this->twitchUrl.$this->streamsEndpoint."/".$this->name);
		return $stream;
	}

	/**
     * Return channel panels data
     *
     * @return Panels
     */
	public function getPanels()
	{
		$panels = $this->getData($this->twitchUrl2.$this->channelsEndpoint."/".$this->name.$this->panelsEndpoint);
		return $panels;
	}

	/**
     * Return twitter username
     *
     * @return Twitter
     */
	public function getTwitter()
	{
		$panels = $this->getPanels();
		foreach ($panels as $panel)
        {
            foreach ($panel as $data)
            {
                if (is_array($data))
                {
                    foreach ($data as $inner)
                    {
                        if (preg_match("|https?://(www\.)?twitter\.com/(#!/)?@?([^/\")]*)|i", $inner, $matches))
                        {
                            return $matches[3];
                            break;
                        }     
                    }
                }
                else
                {
                    if (preg_match("|https?://(www\.)?twitter\.com/(#!/)?@?([^/\")]*)|i", $data, $matches))
                    {
                        return $matches[3];
                        break;
                    }  
                }
            }
        }
        return false;
	}

	/**
     * Return sub badge
     *
     * @return string
     */
	public function getBadge()
	{
		$badge = $this->getData($this->twitchUrl.$this->chatEndpoint."/".$this->name."/badges");
		if (isset($badge['subscriber']['image']))
		{
			return $badge['subscriber']['image'];
		}
		else
		{
			return null;
		}
	}

	/**
     * Check if user is following this channel
     *
     * @return boolean
     */
	public function isFollowing($user)
	{
		$following = $this->getData($this->twitchUrl.$this->usersEndpoint."/".$user->name."/follows/channels/".$this->name);
		if (isset($following['error']))
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	/**
     * Check if user is subscribed to this channel
     *
     * @return boolean
     */
	public function isSubscribed($user)
	{
		$subscribed = $this->getData($this->twitchUrl.$this->usersEndpoint."/".$user->name."/subscriptions/".$this->name, $user->twitch_token);
		if (isset($subscribed['error']))
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	/**
     * Update twitch data
     */
	public function updateData()
	{
		$twitchChannel = TwitchChannel::where('name', $this->name)->first();

		// update twitch channel & stream if it hasn't been updated for 5 minutes
		if ($twitchChannel && ($twitchChannel->updated_at < Carbon::now()->subMinutes(5)))
		{
			// get data
			$channel = $this->getChannel();
			$stream = $this->getStream();

			$twitchChannel->mature 							= $channel['mature'];
			$twitchChannel->status 							= $channel['status'];
			$twitchChannel->broadcaster_language 			= $channel['broadcaster_language'];
			$twitchChannel->display_name 					= $channel['display_name'];
			$twitchChannel->game 							= $channel['game'];
			$twitchChannel->language 						= $channel['language'];
			$twitchChannel->twitch_updated_at 				= $channel['updated_at'];
			$twitchChannel->logo 							= $channel['logo'];
			$twitchChannel->banner 							= $channel['banner'];
			$twitchChannel->video_banner 					= $channel['video_banner'];
			$twitchChannel->background 						= $channel['background'];
			$twitchChannel->profile_banner 					= $channel['profile_banner'];
			$twitchChannel->profile_banner_background_color = $channel['profile_banner_background_color'];
			$twitchChannel->partner 						= $channel['partner'];
			$twitchChannel->sub_badge                      	= $this->getBadge();
			$twitchChannel->views 							= $channel['views'];
			$twitchChannel->followers 						= $channel['followers'];
			$twitchChannel->save();

			// update twitch stream if it's live otherwise delete it
	        if (isset($stream['stream']) && !is_null($stream['stream']))
	        {
	        	$twitchStream = TwitchStream::where('name', $this->name)->first();
	        	if (!$twitchStream)
	        	{
	        		$twitchStream 					= new TwitchStream;
	        		$twitchStream->owner            = $twitchChannel->owner;
	        		$twitchStream->name             = $stream['stream']['channel']['name'];
	        	}
                $twitchStream->game                 = $stream['stream']['game'];
                $twitchStream->viewers              = $stream['stream']['viewers'];
                $twitchStream->average_fps          = $stream['stream']['average_fps'];
                $twitchStream->video_height         = $stream['stream']['video_height'];
                $twitchStream->twitch_created_at    = $stream['stream']['created_at'];
                $twitchStream->twitch_id           	= $stream['stream']['_id'];
                $twitchStream->delay                = $stream['stream']['delay'];
                $twitchStream->is_playlist          = $stream['stream']['is_playlist'];
                $twitchStream->preview              = $stream['stream']['preview']['template'];
                $twitchStream->save();
	        }
	        else
	        {
	        	TwitchStream::where('name', $this->name)->delete();
	        }

	        // save emoticons
            $twitchData = new TwitchData();
            $emoticons = $twitchData->getEmoticons($this->name);
            foreach ($emoticons['emoticons'] as $emoticon)
            {
                if ($emoticon['subscriber_only'])
                {
                    $emote                      = Emoticon::firstOrCreate(['regex' => $emoticon['regex']]);
                    $emote->site_id             = Site::where('domain', $this->name)->value('id');
                    $emote->regex               = $emoticon['regex'];
                    $emote->width               = $emoticon['width'];
                    $emote->height              = $emoticon['height'];
                    $emote->url                 = $emoticon['url'];
                    $emote->state               = $emoticon['state'];
                    $emote->subscriber_only     = $emoticon['subscriber_only'];
                    $emote->save();
                }  
            }

            return "Twitch data updated.";
		}
		else
		{
			return "Twitch data has already been updated recently.";
		}
	}

	/**
     * Return data object
     *
     * @param $url
     * @return Response
     */
	private function getData($url, $token = false)
	{
		if ($token)
		{
			$url .= "?oauth_token=".$token;
		}
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_ENCODING , "gzip");
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		//curl_setopt($ch, CURLOPT_VERBOSE, true);

		$response = curl_exec($ch);
		$headers = curl_getinfo($ch);
		//print_r($headers);
		//echo "<br /><br />";
		$http_code = $headers['http_code'];
		$response = json_decode($response, true);
		return $response;
	}


}