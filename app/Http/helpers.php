<?php

	function parseEmoticons($content)
	{
		$emoticons = App\Models\Emoticon::where('subscriber_only', false)->get();
		// check global emotes
		foreach ($emoticons as $emoticon)
		{
			$content = str_replace($emoticon->regex, "<img alt='".$emoticon->regex."' src='".$emoticon->url."' />" , $content);
		}
		if (Gate::allows('post-media'))
        {
			$emoticons = App\Models\Emoticon::where('subscriber_only', true)->where('site_id', session('site_id'))->get();
			// check sub emotes
			foreach ($emoticons as $emoticon)
			{
				$content = str_replace($emoticon->regex, "<img alt='".$emoticon->regex."' src='".$emoticon->url."' />" , $content);
			}
		}

		return $content;
	}

	function avatar($identifier)
	{
		if (is_numeric($identifier))
		{
			$avatar = App\Models\User::where('id', $identifier)->value('avatar');
		}
		else
		{
			$avatar = App\Models\User::where('name', $identifier)->value('avatar');
		}

		if (is_null($avatar))
		{
			return '//static-cdn.jtvnw.net/jtv_user_pictures/xarth/404_user_150x150.png';
		}
		else
		{
			return substr($avatar, 5);
		}
	}

	function banner($banner)
	{
		if (is_null($banner))
		{
			return '//www-cdn.jtvnw.net/images/xarth/bg_glitch_pattern.png';
		}
		else
		{
			return substr($banner, 5);
		}
	}

	function getStringBetween($content, $start, $end)
	{
		$r = explode($start, $content);
		if (isset($r[1]))
		{
			$r = explode($end, $r[1]);
			return $r[0];
		}
		return '';
	}

	function convertTime($tm, $rcs = 0)
	{
		$cur_tm 	= time();
		$dif 		= $cur_tm-$tm;
		$pds 		= ['second','minute','hour','day','week','month','year','decade'];
		$lngh 		= [1, 60, 3600, 86400, 604800, 2630880, 31570560, 315705600];

		for($v = sizeof($lngh)-1; ($v >= 0)&&(($no = $dif/$lngh[$v])<=1); $v--); if($v < 0) $v = 0; $_tm = $cur_tm-($dif%$lngh[$v]);
		$no = floor($no); if($no <> 1) $pds[$v] .='s'; $x=sprintf("%d %s",$no,$pds[$v]);

		if (($rcs > 0)&&($v >= 1)&&(($cur_tm-$_tm) > 0))
		{
			$x .= ", ".convertTime($_tm, --$rcs);
		}
		else
		{
			$x .= " ago";
		}
		return $x;
	}

?>
