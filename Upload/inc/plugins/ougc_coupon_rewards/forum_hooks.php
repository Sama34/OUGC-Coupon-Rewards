<?php

/***************************************************************************
 *
 *	OUGC Coupon Rewards plugin (/inc/plugins/ougc_coupon_rewards/forum_hooks.php)
 *	Author: Omar Gonzalez
 *	Copyright: © 2020 Omar Gonzalez
 *
 *	Website: https://ougc.network
 *
 *	Allow users to exchange coupon codes for points or memberships.
 *
 ***************************************************************************
 
****************************************************************************
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/

namespace OUGCCouponRewards\ForumHooks;

function global_start()
{
	global $templatelist;

	if(isset($templatelist))
	{
		$templatelist .= ',';
	}
	else
	{
		$templatelist = '';
	}

	if(in_array(constant('THIS_SCRIPT'), ['newpoints.php', 'modcp.php']))
	{
		$templatelist .= ', ougccouponrewards_form, ougccouponrewards_menu, ougccouponrewards_row, ougccouponrewards_option, ougccouponrewards, ougccouponrewards_modcp_nav, ougccouponrewards_empty, ougccouponrewards_row_email';
	}
}

function newpoints_default_menu(&$menu)
{
	global $mybb, $lang, $templates;

	if(is_member($mybb->settings['ougc_coupon_rewards_modgroups']))
	{
		\OUGCCouponRewards\Core\load_language();

		$url = 'newpoints.php';

		$i = $mybb->get_input('action') == 'coupons' ? '&raquo; ' : '';

		$menu[] = eval($templates->render('ougccouponrewards_menu'));
	}
}

function pre_output_page(&$page)
{
	if(my_strpos($page, '<!--OUGC_COUPON_REWARDS_FORM-->') === false)
	{
		return;
	}

	global $mybb, $lang, $templates, $theme, $gobutton;

	\OUGCCouponRewards\Core\load_language();

	$url = 'misc.php';

	if(function_exists('newpoints_format_points'))
	{
		$url = 'newpoints.php';
	}

	$form = eval($templates->render('ougccouponrewards_form'));

	$page = str_replace('<!--OUGC_COUPON_REWARDS_FORM-->', $form, $page);
}

function misc_start($isNewpoints=false)
{
	global $mybb, $db, $lang, $theme, $header, $templates, $headerinclude, $footer, $options, $cache;

	if(
		$mybb->get_input('action') != 'coupons' ||
		$mybb->get_input('do', \MyBB::INPUT_STRING) != 'exchange' ||
		$mybb->request_method != 'post' ||
		($isNewpoints && constant('THIS_SCRIPT') == 'misc.php')
	)
	{
		return;
	}

	\OUGCCouponRewards\Core\load_language();

	$mybb->user['uid'] = (int)$mybb->user['uid'];

	if(!$mybb->user['uid'])
	{
		error_no_permission();
	}

	$usergroups = $cache->read('usergroups');

	$navigation = '';

	$modcp = !function_exists('newpoints_format_points');

	add_breadcrumb($lang->ougc_coupon_rewards_page_title, 'misc.php?action=coupons');

	$url = 'newpoints.php';

	if($modcp)
	{
		$url = 'misc.php';

		$page_title = $lang->ougc_coupon_rewards_page_title;
	}
	else
	{
		$navigation = eval($templates->render('ougccouponrewards_navigation'));

		$page_title = "{$lang->newpoints} - {$lang->ougc_coupon_rewards_page_title}";
	}

	verify_post_check($mybb->get_input('my_post_code'));

	$_code = $db->escape_string(my_strtolower($mybb->get_input('code', \MyBB::INPUT_STRING)));

	$query = $db->simple_select('ougc_coupon_rewards_codes', '*', "code='{$_code}' AND active='1'");

	if(!$db->num_rows($query))
	{
		error($lang->ougc_coupon_rewards_error_code);
	}

	$coupon = $db->fetch_array($query);

	$coupon['cid'] = (int)$coupon['cid'];

	$query = $db->simple_select(
		'ougc_coupon_rewards_logs',
		'lid',
		"cid='{$coupon['cid']}' AND uid='{$mybb->user['uid']}'",
		['limit' => 1]
	);

	if($db->num_rows($query))
	{
		error($lang->ougc_coupon_rewards_error_code);
	}

	$coupon['title'] = htmlspecialchars_uni($coupon['title']);

	$coupon['description'] = htmlspecialchars_uni($coupon['description']);

	$coupon['code'] = htmlspecialchars_uni($coupon['code']);

	$coupon['stock'] = (int)$coupon['stock'];

	if($coupon['stock'] !== -1 && $coupon['stock'] < 1)
	{
		\OUGCCouponRewards\Core\update_outofstock();

		error($lang->ougc_coupon_rewards_error_code);
	}

	if($mybb->settings['ougc_coupon_rewards_allow_email'] && $coupon['email'])
	{
		if(my_strtolower(trim($mybb->user['email'])) != my_strtolower(trim($coupon['email'])))
		{
			error($lang->ougc_coupon_rewards_error_code);
		}
	}

	$stock = my_number_format($coupon['stock']);

	$coupon['gid'] = (int)$coupon['gid'];

	$coupon['type'] = (int)$coupon['type'];

	$type = $lang->ougc_coupon_rewards_group_none;

	if(
		$coupon['gid'] &&
		!empty($usergroups[$coupon['gid']]) &&
		!$usergroups[$coupon['gid']]['isbannedgroup'] &&
		!$usergroups[$coupon['gid']]['cancp'] &&
		!$usergroups[$coupon['gid']]['canmodcp']
	)
	{
		$group = htmlspecialchars_uni($usergroups[$coupon['gid']]['title']);

		$group = format_name($group, $coupon['gid']);

		if($coupon['type'] === 1)
		{
			$type = $lang->ougc_coupon_rewards_type_primary;
		}
		elseif($coupon['type'] === 2)
		{

			$type = $lang->ougc_coupon_rewards_type_secondary;
		}
	}

	// we are going to skip the group change if current user has access to the acp
	if($mybb->settings['ougccouponrewards_redeem_row_groups'] && $coupon['gid'] && (!$mybb->usergroup['cancp'] || $coupon['type'] === 2))
	{
		if($coupon['type'] === 1)
		{
			$user = ['usergroup' => $mybb->user['usergroup'], 'additionalgroups' => ''];
		}
		elseif($coupon['type'] === 2)
		{
			$user = ['usergroup' => 0, 'additionalgroups' => $mybb->user['additionalgroups']];
		}
		if(!is_member($coupon['gid'], $user))
		{
			require_once MYBB_ROOT.'inc/datahandlers/user.php';

			$userhandler = new \UserDataHandler('update');

			$updated_user = [
				'uid' => $mybb->user['uid']
			];

			if($coupon['type'] === 1)
			{
				$updated_user['usergroup'] = $coupon['gid'];
			}
			elseif($coupon['type'] === 2)
			{
				$adds = $coupon['gid'];

				if($mybb->user['additionalgroups'])
				{
					$adds = $mybb->user['additionalgroups'].',';
				}

				$updated_user['additionalgroups'] = $adds;
			}

			$userhandler->set_data($updated_user);

			if(!$userhandler->validate_user())
			{
				$errors = $userhandler->get_friendly_errors();
			}
		}
	}

	$coupon['points'] = (float)$coupon['points'];

	if(empty($errors) && $mybb->get_input('confirm', \MyBB::INPUT_INT))
	{
		if($updated_user)
		{
			$userhandler->update_user();
		}

		if($coupon['stock'] !== -1)
		{
			$db->update_query('ougc_coupon_rewards_codes', [
				'stock' => '`stock`-1'
			], "cid='{$coupon['cid']}'", '', true);

			\OUGCCouponRewards\Core\update_outofstock();
		}

		if($mybb->settings['ougccouponrewards_redeem_row_points'] && $coupon['points'] && !$modcp)
		{
			newpoints_log('ougc_coupon_rewards_logs', my_serialize([
				'cid' => $coupon['cid']
			]));

			newpoints_addpoints($mybb->user['uid'], $coupon['points']);
		}

		$db->insert_query('ougc_coupon_rewards_logs', [
			'cid' => $coupon['cid'],
			'uid' => $mybb->user['uid'],
			'dateline' => TIME_NOW
		]);

		if($modcp)
		{
			$url = '';
		}

		redirect("{$mybb->settings['bburl']}/{$url}", $lang->ougc_coupon_rewards_success_redeem);
	}

	if($errors)
	{
		$errors = inline_error($errors);
	}

	$points = 0;

	if(!$modcp)
	{
		$points = newpoints_format_points($coupon['points']);
	}

	$groups_row = $points_row = '';

	if($mybb->settings['ougccouponrewards_redeem_row_groups'])
	{
		$groups_row = eval($templates->render('ougccouponrewards_row_groups'));
	}

	if($mybb->settings['ougccouponrewards_redeem_row_points'])
	{
		$points_row = eval($templates->render('ougccouponrewards_row_points'));
	}

	$page = eval($templates->render('ougccouponrewards_redeem'));

	output_page($page);

	exit;
}

function newpoints_start10()
{
	misc_start(true);
}

function newpoints_start($modcp=false)
{
	global $mybb, $db, $lang, $theme, $header, $templates, $headerinclude, $footer, $options, $cache;

	\OUGCCouponRewards\Core\load_language();

	$mybb->user['uid'] = (int)$mybb->user['uid'];

	$usergroups = $cache->read('usergroups');

	global $modcp_nav;

	$url = 'newpoints.php';

	if($modcp && is_member($mybb->settings['ougc_coupon_rewards_modgroups']))
	{
		$url = 'modcp.php';

		$nav = eval($templates->render('ougccouponrewards_modcp_nav'));

		$modcp_nav = str_replace('<!--OUGC_COUPON_REWARDS-->', $nav, $modcp_nav);

		$navigation = &$modcp_nav;

		$page_title = $lang->ougc_coupon_rewards_page_title;

		add_breadcrumb($lang->nav_modcp, 'modcp.php');

		add_breadcrumb($page_title, 'modcp.php?action=reports');
	}
	else
	{
		$navigation = eval($templates->render('ougccouponrewards_navigation'));

		$page_title = "{$lang->newpoints} - {$lang->ougc_coupon_rewards_page_title}";
	}

	if($mybb->get_input('action') != 'coupons')
	{
		return;
	}

	if(!is_member($mybb->settings['ougc_coupon_rewards_modgroups']))
	{
		error_no_permission();
	}

	$trow = alt_trow();

	$uid = (int)$mybb->user['uid'];

	$cid = $mybb->get_input('cid', \MyBB::INPUT_INT);

	$type1 = $type2 = '';

	foreach(['title', 'description', 'code', 'stock', 'points', 'unlimited_stock', 'type'] as $key)
	{
		${$key} = '';

		if(empty($mybb->input[$key]))
		{
			continue;
		}

		${$key} = htmlspecialchars_uni($mybb->get_input($key, \MyBB::INPUT_STRING));

		if($key == 'unlimited_stock')
		{
			${$key} = ' checked="checked"';
		}

		if($key == 'type')
		{
			$key = $key.$mybb->get_input($key, \MyBB::INPUT_INT);

			${$key} = ' checked="checked"';
		}
	}

	if($mybb->request_method == 'post')
	{
		\OUGCCouponRewards\Core\update_outofstock();

		if($mybb->get_input('do', \MyBB::INPUT_STRING) == 'update')
		{
			$cids = $mybb->get_input('coupons', \MyBB::INPUT_ARRAY);

			$cids = array_map('intval', $cids);

			if(!$modcp)
			{
				newpoints_log('ougc_coupon_rewards_codes', my_serialize([
					'cids' => implode(",", $cids)
				]));
			}

			foreach($cids as $cid)
			{
				\OUGCCouponRewards\Core\updateCode(['active' => 0], $cid);
			}

			redirect("{$mybb->settings['bburl']}/{$url}?action=coupons", $lang->ougc_coupon_rewards_success_update);
		}

		$errors = [];

		foreach(['title', 'description'] as $key)
		{
			if(empty($mybb->input[$key]))
			{
				$lang_var = 'ougc_coupon_rewards_error_'.$key;

				$errors[] = $lang->$lang_var;
			}
		}

		if($mybb->settings['ougc_coupon_rewards_allow_stock'])
		{
			if(!$mybb->get_input('unlimited_stock', \MyBB::INPUT_INT) && $mybb->get_input('stock', \MyBB::INPUT_INT) < 1)
			{
				$errors[] = $lang->ougc_coupon_rewards_error_stock;
			}
		}

		if($mybb->settings['ougc_coupon_rewards_allow_email'])
		{
			if($mybb->get_input('email') && !validate_email_format(my_strtolower($mybb->get_input('email'))))
			{
				$errors[] = $lang->ougc_coupon_rewards_error_email;
			}
		}

		$existing_code = false;

		if($generated_code = $mybb->get_input('code', \MyBB::INPUT_STRING))
		{
			$_code = $db->escape_string(my_strtolower($generated_code));

			$query = $db->simple_select('ougc_coupon_rewards_codes', 'cid', "code='{$_code}'");

			if($existing_code = $db->num_rows($query))
			{
				$errors[] = $lang->ougc_coupon_rewards_error_duplicated;
			}
		}
		else
		{
			$unique_code = false;

			while($unique_code == false)
			{
				\OUGCCouponRewards\Core\generate_code($generated_code);

				$_code = $db->escape_string(my_strtolower($generated_code));

				$query = $db->simple_select('ougc_coupon_rewards_codes', 'cid', "code='{$_code}'");
	
				if(!($existing_code = $db->num_rows($query)))
				{
					$unique_code = true;
				}
			}
		}

		unset($_code);

		$gid = 0;

		if($mybb->settings['ougc_coupon_rewards_allow_groups'])
		{
			if($gid = $mybb->get_input('gid', \MyBB::INPUT_INT))
			{
				if(
					empty($usergroups[$gid]) ||
					$usergroups[$gid]['isbannedgroup'] ||
					$usergroups[$gid]['cancp'] ||
					$usergroups[$gid]['canmodcp']
				)
				{
					$errors[] = $lang->ougc_coupon_rewards_error_group;
				}
			}
		}

		if($errors)
		{
			$errors = inline_error($errors);
		}
		else
		{
			$insertData = [
				'title' => $mybb->get_input('title', \MyBB::INPUT_STRING),
				'description' => $mybb->get_input('description', \MyBB::INPUT_STRING),
				'code' => $generated_code,
				'stock' => $mybb->get_input('unlimited_stock', \MyBB::INPUT_INT) ? -1 : $mybb->get_input('stock', \MyBB::INPUT_INT),
				'gid' => $gid,
				'type' => $gid ? $mybb->get_input('type', \MyBB::INPUT_INT) : 0,
				'points' => $mybb->get_input('points', \MyBB::INPUT_FLOAT),
				'email' => '',
				'dateline' => TIME_NOW,
			];

			if(!$mybb->settings['ougc_coupon_rewards_allow_stock'])
			{
				$insertData['stock'] = 1;
			}

			if($mybb->settings['ougc_coupon_rewards_allow_groups'])
			{
				$insertData['gid'] = 0;

				$insertData['type'] = 0;
			}

			if($mybb->settings['ougc_coupon_rewards_allow_points'])
			{
				$insertData['points'] = 0;
			}

			if($mybb->settings['ougc_coupon_rewards_allow_email'])
			{
				$insertData['email'] = my_strtolower($mybb->get_input('email'));
			}

			$cid = \OUGCCouponRewards\Core\insertCode($insertData);

			if(!$modcp)
			{
				newpoints_log('ougc_coupon_rewards_codes', my_serialize([
					'cid' => $cid
				]));
			}

			redirect("{$mybb->settings['bburl']}/{$url}?action=coupons", $lang->ougc_coupon_rewards_success_add);
		}
	}

	$disabled = '';

	$where = "active='1'";

	if($mybb->get_input('unactive', \MyBB::INPUT_INT))
	{
		$disabled = ' disabled="disabled"';

		$where = "active='0'";
	}

	$query = $db->simple_select('ougc_coupon_rewards_codes', '*', $where);

	$coupon_list = '';

	if(!$db->num_rows($query))
	{
		$coupon_list = eval($templates->render('ougccouponrewards_empty'));
	}
	else
	{
		while($coupon = $db->fetch_array($query))
		{
			$trow = alt_trow();

			$coupon['cid'] = (int)$coupon['cid'];

			$coupon['title'] = htmlspecialchars_uni($coupon['title']);

			$coupon['description'] = htmlspecialchars_uni($coupon['description']);

			$coupon['code'] = htmlspecialchars_uni($coupon['code']);

			$coupon['stock'] = (int)$coupon['stock'];

			$coupon['stock'] = my_number_format($coupon['stock']);

			$coupon['gid'] = (int)$coupon['gid'];

			$coupon['type'] = (int)$coupon['type'];

			$coupon['email'] = htmlspecialchars_uni($coupon['email']);

			$type = $lang->ougc_coupon_rewards_group_none;

			if(
				$coupon['gid'] &&
				!empty($usergroups[$coupon['gid']]) &&
				!$usergroups[$coupon['gid']]['isbannedgroup'] &&
				!$usergroups[$coupon['gid']]['cancp'] &&
				!$usergroups[$coupon['gid']]['canmodcp']
			)
			{
				$group = htmlspecialchars_uni($usergroups[$coupon['gid']]['title']);

				$group = format_name($group, $coupon['gid']);

				if($coupon['type'] === 1)
				{
					$type = $lang->ougc_coupon_rewards_type_primary;
				}
				elseif($coupon['type'] === 2)
				{
					$type = $lang->ougc_coupon_rewards_type_secondary;
				}
			}

			$coupon['points'] = (float)$coupon['points'];

			$coupon['points'] = 0;

			if(!$modcp)
			{
				$coupon['points'] = newpoints_format_points($coupon['points']);
			}

			$disabled = '';

			if(!$coupon['active'])
			{
				$disabled = ' disabled="disabled"';
			}

			$coupon_list .= eval($templates->render('ougccouponrewards_row'));

			$group = '';
		}
	}

	$groups = '';

	if($usergroups)
	{
		foreach($usergroups as $group)
		{
			if($group['isbannedgroup'] || $group['cancp'] || $group['canmodcp'])
			{
				continue;
			}

			$group['gid'] = (int)$group['gid'];

			$group['title'] = htmlspecialchars_uni($group['title']);

			$checked = '';	

			if($group['gid'] == $mybb->get_input('gid', \MyBB::INPUT_INT))
			{
				$checked = ' selected="selected"';
			}

			$groups .= eval($templates->render('ougccouponrewards_option'));
		}
	}

	$stock_row = $groups_row = $points_row = $email_row = '';

	if($mybb->settings['ougc_coupon_rewards_allow_stock'])
	{
		$stock_row = eval($templates->render('ougccouponrewards_row_stock'));
	}

	if($mybb->settings['ougc_coupon_rewards_allow_groups'])
	{
		$groups_row = eval($templates->render('ougccouponrewards_row_groups'));
	}

	if($mybb->settings['ougc_coupon_rewards_allow_points'])
	{
		$points_row = eval($templates->render('ougccouponrewards_row_points'));
	}

	if($mybb->settings['ougc_coupon_rewards_allow_email'])
	{
		$email_row = eval($templates->render('ougccouponrewards_row_email'));
	}

	$page = eval($templates->render('ougccouponrewards'));

	output_page($page);

	exit;
}

function modcp_start()
{
	if(function_exists('newpoints_format_points'))
	{
		return;
	}

	newpoints_start(true);
}