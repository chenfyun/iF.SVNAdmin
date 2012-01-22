<?php
/**
 * iF.SVNAdmin
 * Copyright (c) 2010 by Manuel Freiholz
 * http://www.insanefactory.com/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.
 */
include("include/config.inc.php");

//
// Authentication
//

if (!$appEngine->isRepositoryViewActive())
{
	$appEngine->forwardInvalidModule(true);
}

$appEngine->checkUserAuthentication(true, ACL_MOD_REPO, ACL_ACTION_VIEW);
$appTR->loadModule("repositoryview");

//
// HTTP Request Vars
//

$varRepoEnc = get_request_var("r");
$varPathEnc = get_request_var("p");
$varRepo = rawurldecode($varRepoEnc);
$varPath = rawurldecode($varPathEnc);

//
// View Data
//

$oR = new \svnadmin\core\entities\Repository();
$oR->name = $varRepo;

try {
	// Get the files of the selected repository path.
	$repoPathList = $appEngine->getRepositoryViewProvider()->listPath($oR, $varPath);

	// Web-Link - Directory Listing
	$apacheWebLink = $appEngine->getConfig()->getValue("Subversion:WebListing", "ApacheDirectoryListing");
	$customWebLink = $appEngine->getConfig()->getValue("Subversion:WebListing", "CustomDirectoryListing");
	$hasApacheWebLink = !empty($apacheWebLink) ? true : false;
	$hasCustomWebLink = !empty($customWebLink) ? true : false;

	// Is the current path the root directory of the repository?
	$isRepositoryRoot = false;
	if ($varPath == NULL || $varPath == "/")
	{
	  $isRepositoryRoot = true;
	}

	// Create the list of directory items.
	// $val->type => 0 is folder, 1 is file.
	$itemList = array();
	foreach ($repoPathList as &$val)
	{
		// Add weblink property.
		if ($hasApacheWebLink || $hasCustomWebLink)
		{
			$args = array($oR->name, $val->getEncodedRelativePath());

			if ($hasApacheWebLink)
			{
				$val->apacheWebLink = IF_StringUtils::arguments($apacheWebLink, $args);
			}

			if ($hasCustomWebLink)
			{
				$val->customWebLink = IF_StringUtils::arguments($customWebLink, $args);
			}
		}
		$itemList[] = $val;
	}

	// Create "up" link.
	// Load the user list template file and add the array of users.
	$backLinkPath = "/";
	if(empty($varPath))
	{
	  $varPath = "";
	}
	else
	{
	  $pos = strrpos($varPath, "/");
	  if ($pos !== false && $pos > 0)
	  {
	    $backLinkPath = substr($varPath, 0, $pos);
	  }
	}

	SetValue("ApacheWebLink", $hasApacheWebLink);
	SetValue("CustomWebLink", $hasCustomWebLink);
	SetValue("ItemList", $itemList);
	SetValue("Repository", $oR);
	SetValue("BackLinkPath", $backLinkPath);
	SetValue("BackLinkPathEncoded", rawurlencode($backLinkPath));
	SetValue("CurrentPath", $varPath);
	SetValue("RepositoryRoot", $isRepositoryRoot);
}
catch (Exception $ex) {
	$appEngine->addException($ex);
}
ProcessTemplate("repository/repositoryview.html.php");
?>