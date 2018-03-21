<?php
namespace WordPress\Plugins\Models;

use WordPress\Shared\Model\ReadOnlyModelSpec;
use WordPress\Sites;

/**
 * Defines the structure for a single plugin
 */
interface PluginSpec extends ReadOnlyModelSpec
{
    
    /**
     * Retrieve this plugin's ID
     *
     * @return string
     */
    public function getID();
    
    /**
    * Retrieves the path to this plugin's file, relative to the plugins directory
    *
    * @return string
    */
    public function getRelativePath();
    
    /**
     * Retrieves this plugin's author name
     *
     * @return string
     */
    public function getAuthorName();
    
    /**
     * Retrieves this plugin author's website
     *
     * @return string
     */
    public function getAuthorURL();
    
    /**
     * Retrieves the description of this plugin's purpose
     *
     * @return string
     */
    public function getDescription();
    
    /**
     * Retrieves the user-friendly name for this plugin's
     *
     * @return string
     */
    public function getName();
    
    /**
     * Retrieves this plugin's version number
     *
     * @return string
     */
    public function getVersion();
    
    /**
     * Indicates this plugin requires global activation on all sites
     *
     * @return bool
     */
    public function requiresGlobalActivation();
    
    
    /***************************************************************************
    *                                 ACTIVATING
    ***************************************************************************/
    
    /**
     * Activate the plugin
     *
     * @param int $siteID The site ID or a \WordPress\Sites constant
     * @return bool True if the plugin is active
     */
    public function activate( int $siteID = Sites::ALL );
    
    /**
     * Can the plugin be activated?
     *
     * @param int $siteID The site ID or a \WordPress\Sites constant
     * @return bool
     */
    public function canActivate( int $siteID = Sites::ALL );
    
    /**
     * Deactivate the plugin
     *
     * @param int $siteID The site ID or a \WordPress\Sites constant
     * @return bool True if the plugin is no longer active
     */
    public function deactivate( int $siteID = Sites::ALL );
    
    /**
     * Is the plugin activated?
     *
     * When checking activated plugins for a single site, also check the
     * globally-activated plugins.
     *
     * @param int $siteID The site ID or a \WordPress\Sites constant
     * @return bool
     */
    public function isActive( int $siteID = Sites::ALL );
}
