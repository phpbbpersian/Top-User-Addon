<?php
/**
*
* @package phpBB3
* @version $Id:
* @copyright (c) 2010 Rich McGirr
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
* Include only once.
*/
if (!defined('INCLUDES_TOP_FIVE_PHP'))
{
	define('INCLUDES_TOP_FIVE_PHP', true);
	
	global $auth, $cache, $user, $db, $phpbb_root_path, $phpEx, $template;

    $user->add_lang('mods/top_five');

	// grab auths that allow a user to read a forum
	$forum_array = array_unique(array_keys($auth->acl_getf('!f_read', true)));

	// we have auths, change the sql query below
	$sql_and = '';
	if (sizeof($forum_array))
	{
		$sql_and = ' AND ' . $db->sql_in_set('t.forum_id', $forum_array, true);
	}
	// grab all posts that meet criteria and auths
	$sql_ary = array(
		'SELECT'	=> 'u.user_id, u.username, u.user_colour, t.topic_title, t.forum_id, t.topic_last_post_id, t.topic_last_post_time, t.topic_last_poster_name',
		'FROM'		=> array(TOPICS_TABLE => 't'),
		'LEFT_JOIN'	=> array(
			array(
				'FROM'	=> array(USERS_TABLE => 'u'),
				'ON'	=> 't.topic_last_poster_id = u.user_id',
   			),
		),
		'WHERE'		=> 't.topic_approved = 1 AND t.topic_status <> ' . ITEM_MOVED . ' ' . $sql_and,
		'ORDER_BY'	=> 't.topic_last_post_time DESC',
	);

	$result = $db->sql_query_limit($db->sql_build_query('SELECT', $sql_ary), 5);
	$is_row = false;
    while( $row = $db->sql_fetchrow($result) )
    {
		$is_row = true;
		$view_topic_url = append_sid("{$phpbb_root_path}viewtopic.$phpEx", 'f=' . $row['forum_id'] . '&amp;p=' . $row['topic_last_post_id'] . '#p' . $row['topic_last_post_id']);
		$topic_title = censor_text($row['topic_title']);
		$is_guest = $row['user_id'] != ANONYMOUS ? false : true;
			
       	$template->assign_block_vars('top_five_topic',array(
       		'U_TOPIC' 		=> $view_topic_url,
       		'USERNAME_FULL'	=> $is_guest ? $user->lang['BY'] . ' ' . get_username_string('full', $row['user_id'], $row['username'], $row['user_colour'], $row['topic_last_poster_name']) : $user->lang['BY'] . ' ' . get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
			'LAST_TOPIC_TIME'	=> $user->format_date($row['topic_last_post_time']),
       		'TOPIC_TITLE' 	=> $user->lang['IN'] . ' ' . $topic_title));
    }

    $db->sql_freeresult($result);

	// if user doesn't have permission to read any forums, show a message
	if (!$is_row)
	{
		$template->assign_block_vars('top_five_topic', array(
			'NO_TOPIC_TITLE'	=> $user->lang['NO_TOPIC_EXIST'],
		));
	}
	// top five posters
	// an array of user types we dont' bother with
	// could add board founder (USER_FOUNDER) if wanted
	$ignore_users = array(USER_IGNORE, USER_INACTIVE);
	
	if (($user_posts = $cache->get('_top_five_posters')) === false)
	{
	    $user_posts = array();

		// grab users with most posts
	    $sql = 'SELECT user_id, username, user_colour, user_posts
	       	FROM ' . USERS_TABLE . '
			WHERE ' . $db->sql_in_set('user_type', $ignore_users, true) . '
				AND user_posts <> 0
	       ORDER BY user_posts DESC';
		$result = $db->sql_query_limit($sql, 5);

		while ($row = $db->sql_fetchrow($result))
		{
			$user_posts[$row['user_id']] = array(
				'user_id'		=> $row['user_id'],
                'username'		=> $row['username'],
                'user_colour'	=> $row['user_colour'],
				'user_posts'    => $row['user_posts'],
			);
		}
        $db->sql_freeresult($result);

		// cache this data for five minutes, this improves performance
		$cache->put('_top_five_posters', $user_posts, 300);
	}

	foreach ($user_posts as $row)
	{
		$username_string = get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']);

		$template->assign_block_vars('top_five_active',array(
			'S_SEARCH_ACTION'	=> append_sid("{$phpbb_root_path}search.$phpEx", 'author_id=' . $row['user_id'] . '&amp;sr=posts'),
			'POSTS' 			=> $row['user_posts'],
			'USERNAME_FULL'		=> $username_string)
		);
    }

    // newest registered users
	if (($newest_users = $cache->get('_top_five_newest_users')) === false)
	{
	    $newest_users = array();

	    // grab most recent registered users
		$sql = 'SELECT user_id, username, user_colour, user_regdate
			FROM ' . USERS_TABLE . '
			WHERE ' . $db->sql_in_set('user_type', $ignore_users, true) . '
				AND user_inactive_reason = 0
			ORDER BY user_regdate DESC';
		$result = $db->sql_query_limit($sql, 5);

		while ($row = $db->sql_fetchrow($result))
		{
			$newest_users[$row['user_id']] = array(
				'user_id'				=> $row['user_id'],
				'username'				=> $row['username'],
     			'user_colour'			=> $row['user_colour'],
                'user_regdate'			=> $row['user_regdate'],
			);
		}
	    $db->sql_freeresult($result);

		// cache this data for ever, cache is purged when adding or deleting users
		$cache->put('_top_five_newest_users', $newest_users);
	}

	foreach ($newest_users as $row)
	{
		$username_string = get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']);

		$template->assign_block_vars('top_five_newest',array(
			'REG_DATE'			=> $user->format_date($row['user_regdate']),
			'USERNAME_FULL'		=> $username_string)
		);
	}
   // phpBB ajax like Add-on
   $sql_array = array(
      'SELECT'    => 'COUNT(l.poster_id) as user_likes, u.user_id, u.username, u.user_colour',

      'FROM'      => array(
         LIKES_TABLE => 'l',
         USERS_TABLE    => 'u'
      ),

      'WHERE'     =>  'l.poster_id = u.user_id',
         
      'GROUP_BY'  => 'l.poster_id',

      'ORDER_BY'  => 'user_likes DESC',
   );
   $sql = $db->sql_build_query('SELECT', $sql_array);
   $result = $db->sql_query_limit($sql, 5);
      while ($row = $db->sql_fetchrow($result))
   {
      $template->assign_block_vars('toplikes', array(
      'LIKECOUNT'      => $row['user_likes'] > 1 ? sprintf($user->lang['USER_LIKES'], $row['user_likes']) : sprintf($user->lang['USER_LIKE'], $row['user_likes']),
      'USERNAME' => $row['username'],
      'USERCOLOUR' => $row['user_colour'],
      'USERLINK' => append_sid("{$phpbb_root_path}memberlist.$phpEx", 'mode=viewprofile&amp;u=' . $row['user_id'] . ''),
      ));
   }
   $db->sql_freeresult($result);
}
?>