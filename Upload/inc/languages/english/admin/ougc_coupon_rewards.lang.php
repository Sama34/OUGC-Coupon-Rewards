<?php

/***************************************************************************
 *
 *	OUGC Coupon Rewards plugin (/inc/languages/english/admin/ougc_coupon_rewards.lang.php)
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

$l = [
	'setting_group_ougc_coupon_rewards' => 'OUGC Coupon Rewards',
	'setting_group_ougc_coupon_rewards_desc' => 'Allow users to request points rewards in exchange of activity.',

	'setting_ougc_coupon_rewards_plugin' => 'Plugin',
	'setting_ougc_coupon_rewards_plugin_desc' => 'Select which points plugin you have installed in this forum.',
	'setting_ougc_coupon_rewards_plugin_newpoints' => 'Newpoints',
	'setting_ougc_coupon_rewards_modgroups' => 'Moderator Groups',
	'setting_ougc_coupon_rewards_modgroups_desc' => 'Select allowed groups to generate new codes.',
	//'setting_ougc_coupon_rewards_minimum' => 'Minimum Codes',
	//'setting_ougc_coupon_rewards_minimum_desc' => 'Select the minimum active codes that should be available for each category.',
	'setting_ougc_coupon_rewards_allow_stock' => 'Allow Stock Assignment',
	'setting_ougc_coupon_rewards_allow_stock_desc' => 'Allow a set amount to be assigned to codes. If disabled stock will be set to <code>1</code> for all coupons.',
	'setting_ougc_coupon_rewards_allow_groups' => 'Allow Groups Assignment',
	'setting_ougc_coupon_rewards_allow_groups_desc' => 'Allow codes to assign groups when redeemed.',
	'setting_ougc_coupon_rewards_allow_points' => 'Allow Points Assignment',
	'setting_ougc_coupon_rewards_allow_points_desc' => 'Allow codes to grant points when redeemed.',
	'setting_ougc_coupon_rewards_allow_email' => 'Allow Email Assignment',
	'setting_ougc_coupon_rewards_allow_email_desc' => 'Allow codes to require specific emails to be redeemed. The email will be checked againts the current user email.',

	'ougc_coupon_rewards_pluginlibrary' => 'This plugin requires <a href="{1}">PluginLibrary</a> version {2} or later to be uploaded to your forum.'
];