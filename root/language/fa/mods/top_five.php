<?php
/**
*
* top_five [English]
*
* @package language
* @version $Id:
* @copyright (c) 2010 Rich McGirr 
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* DO NOT CHANGE
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine

$lang = array_merge($lang, array(
    'NEWEST_TOPICS'		=> 'جدیدترین پست ها',
	'NO_TOPIC_EXIST'	=> 'پستی موجود نیست',
	'TOP_FIVE_ACTIVE'	=> 'فعال ترین کاربران',
    'TOP_FIVE_NEWEST'	=> 'جدیدترین کاربران',
    'IN'                => 'در :',
	'BY'                => 'توسط :',
    'TOP_FIVE_LIKES'    => 'کاربران برتر',
    'USER_LIKE'         => 'این کاربر %s لایک دربافت کرده است.',
    'USER_LIKES'        => 'این کاربر %s لایک دریافت کرده است.',
));

?>
