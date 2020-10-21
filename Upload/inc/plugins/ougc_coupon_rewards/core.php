<?php

/***************************************************************************
 *
 *	OUGC Coupon Rewards plugin (/inc/plugins/ougc_coupon_rewards/core.php)
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

namespace OUGCCouponRewards\Core;

function load_language()
{
	global $lang;

	isset($lang->setting_group_ougc_coupon_rewards) || $lang->load('ougc_coupon_rewards');
}

function load_pluginlibrary($return=false)
{
	global $PL, $lang;

	if($file_exists = file_exists(PLUGINLIBRARY))
	{
		global $PL;
	
		$PL or require_once PLUGINLIBRARY;
	}

	if($return)
	{
		return;
	}

	$_info = \OUGCCouponRewards\Admin\_info();

	if(!$file_exists || $PL->version < $_info['pl']['version'])
	{
		\OUGCCouponRewards\Core\load_language();
	
		flash_message($lang->sprintf($lang->ougc_coupon_rewards_pluginlibrary, $_info['pl']['url'], $_info['pl']['version']), 'error');

		admin_redirect('index.php?module=config-plugins');
	}
}

function addHooks(string $namespace)
{
    global $plugins;

    $namespaceLowercase = strtolower($namespace);
    $definedUserFunctions = get_defined_functions()['user'];

	foreach($definedUserFunctions as $callable)
	{
        $namespaceWithPrefixLength = strlen($namespaceLowercase) + 1;

		if(substr($callable, 0, $namespaceWithPrefixLength) == $namespaceLowercase.'\\')
		{
            $hookName = substr_replace($callable, null, 0, $namespaceWithPrefixLength);

            $priority = substr($callable, -2);

			if(is_numeric(substr($hookName, -2)))
			{
                $hookName = substr($hookName, 0, -2);
			}
			else
			{
                $priority = 10;
            }

            $plugins->add_hook($hookName, $callable, $priority);
        }
    }
}

function generate_code(&$code)
{
	global $mybb;

	$characters = $mybb->settings['ougc_coupon_rewards_characters'] ?: 'a-_bcdefghijklmnopqrstuvwxyz0123456789';

	$length = (int)$mybb->settings['ougc_coupon_rewards_length'];

	$length = $length > 0 && $length < 50 ? $length : 50 ;

	srand((double)microtime()*1000000);

    $code = '';

	for($i=1; $i <= $length; ++$i)
	{
		$code .= substr($characters, rand() % 33, 1);
    }
}

function update_outofstock()
{
	global $db;

	$db->update_query('ougc_coupon_rewards_codes', ['active' => 0], "stock='0'");
}

// Set url
function set_url($url=null)
{
	static $current_url = '';

	if(($url = trim($url)))
	{
		$current_url = $url;
	}

	return $current_url;
}

// Set url
function get_url()
{
	return set_url();
}

// Build an url parameter
function build_url($urlappend=[])
{
	global $PL;

	\OUGCCouponRewards\Core\load_pluginlibrary(true);

	if(!is_object($PL))
	{
		return get_url();
	}

	if($urlappend && !is_array($urlappend))
	{
		$urlappend = explode('=', $urlappend);

		$urlappend = [$urlappend[0] => $urlappend[1]];
	}

	return $PL->url_append(get_url(), $urlappend, '&amp;', true);
}