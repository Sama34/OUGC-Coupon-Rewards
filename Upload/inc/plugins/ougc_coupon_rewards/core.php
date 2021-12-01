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

function generate_code(&$code)
{
	srand((double)microtime()*1000000);

    $code = '';

	for($i=1; $i <= 15; ++$i)
	{
		$code .= substr('abcdefghijklmnopqrstuvwxyz0123456789', rand() % 33, 1);
    }
}

function update_outofstock()
{
	global $db;

	$db->update_query('ougc_coupon_rewards_codes', ['active' => 0], "stock='0'");
}

function insertCode($data, $update=false, $cid=0)
{
	global $db;

	$insertData = [];

	if(isset($data['title']))
	{
		$insertData['title'] = $db->escape_string($data['title']);
	}

	if(isset($data['description']))
	{
		$insertData['description'] = $db->escape_string($data['description']);
	}

	if(isset($data['code']))
	{
		$insertData['code'] = $db->escape_string($data['code']);
	}

	if(isset($data['stock']))
	{
		$insertData['stock'] = (int)$data['stock'];
	}

	if(isset($data['gid']))
	{
		$insertData['gid'] = (int)$data['gid'];
	}

	if(isset($data['type']))
	{
		$insertData['type'] = (int)$data['type'];
	}

	if(isset($data['points']))
	{
		$insertData['points'] = (float)$data['points'];
	}

	if(isset($data['email']))
	{
		$insertData['email'] = $db->escape_string($data['email']);
	}

	if(isset($data['active']))
	{
		$insertData['active'] = (int)$data['active'];
	}

	if(isset($data['dateline']))
	{
		$insertData['dateline'] = (int)$data['dateline'];
	}

	if($update)
	{
		$db->update_query('ougc_coupon_rewards_codes', $insertData, "cid='{$cid}'");

		return true;
	}
	else
	{
		return $db->insert_query('ougc_coupon_rewards_codes', $insertData);
	}
}

function updateCode($data, $cid)
{
	return insertCode($data, true, $cid);
}