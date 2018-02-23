<?php
namespace WordPress\Plugins\Models;

use WordPress\Sites;

/**
 * Represents a single WordPress plugin
 */
class Plugin extends _Plugin
{
    
    /**
     * Name of the plugin's author
     *
     * @var string
     */
    private $authorName;
    
    /**
     * Link to the author's website
     *
     * @var string
     */
    private $authorURL;
    
    /**
     * Description of the plugin's purpose
     *
     * @var string
     */
    private $description;
    
    /**
     * User-friendly name for the plugin
     *
     * @var string
     */
    private $name;
    
    /**
    * Path to plugin file, relative to the plugins directory
    *
    * @var string
    */
    private $relativePath;
    
    /**
    * Indicates this plugin requires global activation on all sites
    *
    * @var string
    */
    private $requiresGlobalActivation;
    
    /**
     * Plugin version number
     *
     * @var string
     */
    private $version;
    
    
    /**
     * Instantiate a new Plugin instance
     *
     * @param string $relativePath Path to plugin file, relative to the plugins directory
     * @param bool   $requiresGlobalActivation Indicates this plugin requires global activation on all sites
     * @param string $version      Plugin version number
     * @param string $name         User-friendly name for the plugin
     * @param string $description  Description of the plugin's purpose
     * @param string $authorName   Name of the plugin's author
     * @param string $authorURL    Link to the author's website
     */
    final public function __construct( string $relativePath,
                                       bool   $requiresGlobalActivation,
                                       string $version,
                                       string $name,
                                       string $description,
                                       string $authorName,
                                       string $authorURL )
    {
        $this->relativePath             = $relativePath;
        $this->requiresGlobalActivation = $requiresGlobalActivation;
        $this->version                  = $version;
        $this->name                     = $name;
        $this->description              = $description;
        $this->authorName               = $authorName;
        $this->authorURL                = $authorURL;
    }
    
    final public function getAuthorName()
    {
        return $this->authorName;
    }
    
    final public function getAuthorURL()
    {
        return $this->authorURL;
    }
    
    final public function getDescription()
    {
        return $this->description;
    }
    
    final public function getName()
    {
        return $this->name;
    }
    
    final public function getRelativePath()
    {
        return $this->relativePath;
    }
    
    final public function getVersion()
    {
        return $this->version;
    }
    
    final public function requiresGlobalActivation()
    {
        return $this->requiresGlobalActivation;
    }
    
    
    /***************************************************************************
    *                                 ACTIVATING
    ***************************************************************************/
    
    /**
     * Activate the plugin
     *
     * @param int $siteID The site ID or a \WordPress\Sites constant
     * @return bool Whether or not the plugin was successfully activated.
     */
    final public function activate( int $siteID = Sites::ALL )
    {
        // Variables
        $siteID   = Sites::SanitizeID( $siteID );
        $isActive = false;
        
        if ( $this->canActivate( $siteID )) {
            
            // Activate globally, for all sites
            if ( Sites::ALL === $siteID ) {
                $result   = activate_plugin( $this->getRelativePath(), null, true );
                $isActive = !is_wp_error( $result );
            }
            
            // Activate on the single site
            else {
                \WordPress\Sites::SwitchTo( $siteID );
                $result   = activate_plugin( $this->getRelativePath() );
                $isActive = !is_wp_error( $result );
                \WordPress\Sites::SwitchBack();
            }
        }
        return $isActive;
    }
    
    
    /**
     * Can the plugin be activated?
     *
     * @param int $siteID The site ID or a \WordPress\Sites constant
     * @return bool
     */
    final public function canActivate( int $siteID = Sites::ALL )
    {
        // Variables
        $siteID = Sites::SanitizeID( $siteID );
        
        // Evaluate
        return (
            Sites::INVALID !== $siteID     &&
            !$this->isActivated( $siteID ) &&
            (
                !is_multisite()                    ||
                !$this->requiresGlobalActivation() ||
                Sites::ALL === $siteID
            )
        );
    }
    
    /**
     * Deactivate the plugin
     *
     * @param int $siteID The site ID or a \WordPress\Sites constant
     * @return bool Whether or not the plugin was successfully deactivated
     */
    final public function deactivate( int $siteID = Sites::ALL )
    {
        // Variables
        $isSuccessful = true;
        $siteID       = Sites::SanitizeID( $siteID );
        
        // Deactivate globally, for all sites
        if ( Sites::ALL === $siteID ) {
            deactivate_plugins( $this->getRelativePath(), false, true );
            $isSuccessful = !$this->isActivated( Sites::ALL );
        }
        
        // Deactivate for a single site
        elseif ( Sites::INVALID !== $siteID ) {
            Sites::SwitchTo( $siteID );
            deactivate_plugins( $this->getRelativePath(), false, false );
            $isSuccessful = (
                // Ignore global activation, only check single site
                $this->isActivated( Sites::ALL ) ||
                !$this->isActivated( $siteID )
            );
            Sites::SwitchBack();
        }
        
        return $isSuccessful;
    }
    
    
    /**
     * Is the plugin activated?
     *
     * When checking activated plugins for a single site, also check the
     * globally-activated plugins.
     *
     * @param int $siteID The site ID or a \WordPress\Sites constant
     * @return bool
     */
    final public function isActivated( int $siteID = Sites::ALL )
    {
        // Variables
        $isActivated = false;
        $siteID      = Sites::SanitizeID( $siteID );
        
        // Determine if plugin is activate globally, for all sites
        if ( Sites::ALL === $siteID ) {
            $isActivated = is_plugin_active_for_network( $this->getRelativePath() );
        }
        
        // Determine if plugin is activated for a single site
        elseif ( Sites::INVALID !== $siteID ) {
            
            // Include files
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            
            // Lookup active plugins for the site
            Sites::SwitchTo( $siteID );
            $isActivated = is_plugin_active( $this->getRelativePath() );
            Sites::SwitchBack();
        }
        
        // Plugin not active
        return $isActivated;
    }
}
