<?php
/*
Changes in v1.1.2 :
[add] The p() function now accepts two new variables - a function to be called and another sitefile

Changes in v1.1.1 :
[add] You can define your own default sitefile to be called as the default one
[mod] Sitefile classes are now instantiated and it's object methods are called instead of calling the static functions
*/
error_reporting(E_ALL|E_STRICT); // E_STRICT compliant.

ob_start();

// You can check to see if 'S' is defined to ensure that the script is not being 
// called directly from outside of ShortPHP's environment
define('S',1);

// include shared.php code if it exists
if (file_exists($g='shared.php')) include $g;


// Sub-path path to the currently running script .. if we're directly on top of a
// domain, this will probably be empty, else for instance: /john/mysite
$S_SUBPATH = str_replace('/index.php','',$_SERVER['PHP_SELF']);

// Now we're going to break the URI parts down by exploding by a slash / - we'll
// Take care of some minimum sanitizing while we're at it... This will probably
// have to be rethought at some point.
$S_URI_PARTS = array_slice(explode( '/', urldecode(trim(str_replace(array("\\","\0","\n","\r","<",">","\"","'"),'',str_replace( $S_SUBPATH,'',$_SERVER['REQUEST_URI'])))) ), 1);

// Let's always set a default site file and function by hardcoding them in if they're
// not given... Later we'll have to provide some mechanism for the developer to control
// which site file and function will be the default ones.
// 
// *** Important: It's not good to mix the thought of a default action/function with
// *** a fallback one, as used in the class/function loading code! It serves a different purpose.

if (!defined('S_DEFAULT_SITEFILE')) define('S_DEFAULT_SITEFILE','home');
$S_URI_PARTS[0] = isset($S_URI_PARTS[0]) ? ($S_URI_PARTS[0]?$S_URI_PARTS[0]:S_DEFAULT_SITEFILE) : S_DEFAULT_SITEFILE;
$S_URI_PARTS[1] = isset($S_URI_PARTS[1]) ? ($S_URI_PARTS[1]?$S_URI_PARTS[1]:'index') : 'index';

// Set a default title as the current site file
$S_TITLE = $S_URI_PARTS[0];

// Include the site file - this is the main code and all the actions take place here, except
// for when the site file defines a class etc... then usually there's not much happening here..
// If the file does not exist, load a 404 site file as default
include file_exists('site/'.$S_URI_PARTS[0].'.php') ? 'site/'.$S_URI_PARTS[0].'.php' : 'site/404.php';



// Now we need to see if the class is available and see if we can call a function
if (class_exists($S_URI_PARTS[0])) {
	
	// Let's disallow function calls starting with _
	if (substr($S_URI_PARTS[1],0,1) == '_') {
		echo "Error: You can't call functions starting with '_' !!!<br />";
	}
		
	// Instantiate an object of the class in the sitefile - the default constructor gets called of course
	$S_SITE_OBJECT = new $S_URI_PARTS[0]();
	
	// Check to see if the desired function exists inside the class and call it... fall back to index()
	if (method_exists($S_URI_PARTS[0],$S_URI_PARTS[1])) {
		$S_SITE_OBJECT->{$S_URI_PARTS[1]}();
	} else {
		$S_SITE_OBJECT->index();
	}

	
}

// Store the site file's output
$S_CONTENT = ob_get_clean();

// Functions to use within the main index/template file

// Get the content
function c () {
	return $GLOBALS['S_CONTENT'];
}

// Set and/or get the title of the website
function t ($t='') {	
	return $t?($GLOBALS['S_TITLE']=$t):$GLOBALS['S_TITLE'];
}

// Get the complete absolute file path of the site1
function f () {
	return $_SERVER['DOCUMENT_ROOT'].$GLOBALS['S_SUBPATH'];
}

// Get the complete absolute http path of the root of the website.
// You can add your own sitefile + function
// If a function is defined but no pagefile, you get the current one
function p ($f='',$s='') {
	$add = $f ? '/'.($s?$s.'/'.$f:uri(0).'/'.$f) : '';
	return 'http://'.$_SERVER['SERVER_NAME'].$GLOBALS['S_SUBPATH'].$add;
}
// Retrieve a part of the URI string...
function uri ($nr=0) {
	return isset($GLOBALS['S_URI_PARTS'][$nr]) ? $GLOBALS['S_URI_PARTS'][$nr] : '';
}


// If the variable s_notpl is set in the query string or s_notpl is defined (for instance
// by a site file), than we won't be completing the rest of the script (the template file), but
// simply output what's been done and then exit the script
if (isset($_GET['s_notpl']) || defined('s_notpl')) { 
	die($S_CONTENT);
}

?>