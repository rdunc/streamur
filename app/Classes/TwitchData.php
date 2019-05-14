<?php 

namespace App\Classes;
 
use App\Models\TwitchChannel;
use App\Models\TwitchStream;
use Carbon;

class TwitchData
{

	private $twitchUrl 				= "https://api.twitch.tv/kraken";
	private $emoticonsEndpoint 		= "/chat";


	/**
	* Create a new twitch class instance.
	*
	* @return void
	*/
	public function __construct()
	{

	}

	/**
     * Return twitch user object
     *
     * @return User
     */
	public function getEmoticons($channel)
	{
		$user = $this->getData($this->twitchUrl.$this->emoticonsEndpoint.'/'.$channel.'/emoticons');
		return $user;
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