<?php

$GLOBALS['saigns_apache'] = array();

if ( isset($_GET["apache"]) && strlen($_GET["apache"]) > 1 )
{
    $GLOBALS['saigns_apache'] = explode("/",str_replace("?","",$_GET["apache"]));
    
    //Register all URI elements with a "=" as http request parameter
    foreach ( $GLOBALS['saigns_apache'] as $s7 )
    {
        if ( strpos($s7,"=") !== FALSE )
        {
            $ex = explode("=",$s7,2);
            $_REQUEST[$ex[0]] = $ex[1];
        }
    }
}

/**
 * Get the value of a given URI element
 *
 * @param unknown $index
 * @return unknown|string
 */
function apache($index)
{
    if ( isset($GLOBALS['saigns_apache']) && isset($GLOBALS['saigns_apache'][$index]) )
    {
        return $GLOBALS['saigns_apache'][$index];
    }
    return '';
}

/**
 * Get the value of a given http request parameter
 *
 * @param unknown $p
 * @return unknown|string
 */
function g($p)
{
    if ( isset($_REQUEST[$p]) )
    {
        return $_REQUEST[$p];
    }
    return '';
}

/**
 * Get the value of a given http cookie
 *
 * @param unknown $p
 * @return unknown|string
 */
function c($p)
{
    if ( isset($_COOKIE[$p]) )
    {
        return $_COOKIE[$p];
    }
    return '';
}

?>