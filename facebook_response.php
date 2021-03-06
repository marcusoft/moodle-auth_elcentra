<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'auth_elcentra', language 'en'.
 *
 * @package   auth_elcentra
 * @copyright 2013 onwards Elcentra
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once '../../lib/pluginlib.php';
require_once 'eblib/facebook.php';
require_once 'eblib/misc.php';

$error = optional_param("error", false, PARAM_RAW);

if($error!==false) {
	if($error == "access_denied") {
		loginDenied("facebook");
	} else {
		$errorCode = required_param("error_code", PARAM_RAW);
		$errorDesc = required_param("error_description", PARAM_RAW);
		throw new moodle_exception("$errorDesc - ($error: $errorCode)");
	}
}

$code = required_param('code', PARAM_RAW);
$state = required_param("state", PARAM_RAW);

global $PAGE;

$PAGE->set_url("/auth/elcentra/facebook_response.php", array("code"=>$code, "state"=>$state));

$pluginManager = plugin_manager::instance();

if(!$pluginManager->get_plugin_info("auth_elcentra", true)->is_enabled()) {
	throw new moodle_exception("Enable elcentra plugin");
}

if(isset($_SESSION['facebook_login']['state']) && isset($_GET['state']) && ($_SESSION['facebook_login']['state']==$state)) {
	$facebook = new EbuildersFacebook();
	$conf = get_config("auth/elcentra");

	$facebookConfig = array(
		"app_id"=>$conf->facebookclientid,
		"app_secret"=>$conf->facebookclientsecret,
		"base_url"=>$conf->facebook_base_url,
		"token_access_url"=>$conf->facebook_token_access_url,
		"retrieval_url"=>$conf->facebook_retrieval_url,
		"scope"=>$conf->facebook_scope
		);
	
	if($facebookConfig['app_id']=="" || $facebookConfig['app_secret']=="") {
		throw new moodle_exception("Facebook login is not configured. Contact admin");
	}
	
	$facebook->setConfig($facebookConfig);
	$facebookReturn = $facebook->receiveResponse($code);
	
	$prefix = "elcentra_fb_";
	
	$accountDetails = array(
			"$prefix".$facebookReturn->id, //Username
			$facebookReturn->email, //Email
			$facebookReturn->first_name,
			$facebookReturn->last_name,
			"", //Country
			"", //City
			$facebookReturn->timezone, //Timezone
			$facebookReturn->verified, //Verified
			);
	
	require 'auth.php';
	$elcentraPlugin = new auth_plugin_elcentra();
	$elcentraPlugin->elcentraProcessResponse($accountDetails);
}