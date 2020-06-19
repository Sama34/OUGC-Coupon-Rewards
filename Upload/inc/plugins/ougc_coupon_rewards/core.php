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

function load_pluginlibrary()
{
	global $PL, $lang;

	\OUGCCouponRewards\Core\load_language();

	$_info = \OUGCCouponRewards\Admin\_info();

	if($file_exists = file_exists(PLUGINLIBRARY))
	{
		global $PL;
	
		$PL or require_once PLUGINLIBRARY;
	}

	if(!$file_exists || $PL->version < $_info['pl']['version'])
	{
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

function update_cache(&$packages)
{
	global $mybb, $db;

	$query = $db->simple_select('ougc_coupon_rewards_packages', '*', "active='1' AND points>'0'");

	$packages = [];

	while($package = $db->fetch_array($query))
	{
		$packages[(int)$package['cid']] = $package;
	}

	$mybb->cache->update('ougc_coupon_rewards_packages', $packages);
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

function generate_code(&$code)
{
	srand((double)microtime()*1000000);

    $code = '';

	for($i=1; $i <= 15; ++$i)
	{
		$code .= substr('abcdefghijklmnopqrstuvwxyz0123456789', rand() % 33, 1);
    }
} 

// control_object by Zinga Burga from MyBBHacks ( mybbhacks.zingaburga.com ), 1.68
function control_object(&$obj, $code) {
	static $cnt = 0;
	$newname = '_objcont_'.(++$cnt);
	$objserial = serialize($obj);
	$classname = get_class($obj);
	$checkstr = 'O:'.strlen($classname).':"'.$classname.'":';
	$checkstr_len = strlen($checkstr);
	if(substr($objserial, 0, $checkstr_len) == $checkstr) {
		$vars = array();
		// grab resources/object etc, stripping scope info from keys
		foreach((array)$obj as $k => $v) {
			if($p = strrpos($k, "\0"))
				$k = substr($k, $p+1);
			$vars[$k] = $v;
		}
		if(!empty($vars))
			$code .= '
				function ___setvars(&$a) {
					foreach($a as $k => &$v)
						$this->$k = $v;
				}
			';
		eval('class '.$newname.' extends '.$classname.' {'.$code.'}');
		$obj = unserialize('O:'.strlen($newname).':"'.$newname.'":'.substr($objserial, $checkstr_len));
		if(!empty($vars))
			$obj->___setvars($vars);
	}
	// else not a valid object or PHP serialize has changed
}