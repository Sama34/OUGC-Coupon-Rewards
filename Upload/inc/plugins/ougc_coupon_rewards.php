<?php

/***************************************************************************
 *
 *	OUGC Coupon Rewards plugin (/inc/plugins/ougc_coupon_rewards.php)
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
 
// Die if IN_MYBB is not defined, for security reasons.
if(!defined('IN_MYBB'))
{
	die('This file cannot be accessed directly.');
}

define('OUGC_COUPON_REWARDS_ROOT', MYBB_ROOT . 'inc/plugins/ougc_coupon_rewards');

require_once OUGC_COUPON_REWARDS_ROOT.'/core.php';

// Add our hooks
if(defined('IN_ADMINCP'))
{
	require_once OUGC_COUPON_REWARDS_ROOT.'/admin.php';
}
else
{
	require_once OUGC_COUPON_REWARDS_ROOT.'/forum_hooks.php';

	\OUGCCouponRewards\Core\addHooks('OUGCCouponRewards\ForumHooks');
}

// Plugin API
function ougc_coupon_rewards_info()
{
	return \OUGCCouponRewards\Admin\_info();
}

// Activate the plugin.
function ougc_coupon_rewards_activate()
{
	\OUGCCouponRewards\Admin\_activate();
}

// Deactivate the plugin.
function ougc_coupon_rewards_deactivate()
{
	\OUGCCouponRewards\Admin\_deactivate();
}

// Install the plugin.
function ougc_coupon_rewards_install()
{
	\OUGCCouponRewards\Admin\_install();
}

// Check if installed.
function ougc_coupon_rewards_is_installed()
{
	return \OUGCCouponRewards\Admin\_is_installed();
}

// Unnstall the plugin.
function ougc_coupon_rewards_uninstall()
{
	\OUGCCouponRewards\Admin\_uninstall();
}