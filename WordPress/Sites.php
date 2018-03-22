<?php
namespace WordPress;

use PHP\Collections\Dictionary\ReadOnlyDictionary;
use PHP\Collections\Dictionary\ReadOnlyDictionarySpec;

/**
 * Manages WordPress sites
 *
 * IMPORTANT: Some calls may bomb if the WordPress functions are not yet loaded.
 * If this happens, you will either want to 1) delay the call the routine, or
 * 2) load the needed WordPress files by hand.
 */
class Sites
{
    
    /**
     * Pseudo-ID for all sites
     *
     * @var int
     */
    const ALL = -1;
    
    /**
     * Pseudo-ID for the current site
     *
     * @var int
     */
    const CURRENT = 0;
    
    /**
     * Pseudo-ID for an invalid site ID
     *
     * @var int
     */
    const INVALID = -2;
    
    
    /**
     * Cache of all sites
     *
     * @var \PHP\Cache
     */
    private static $cache;
    
    
    /**
     * Initializes the sites manager (automatically invoked on class load)
     */
    public static function Initialize()
    {
        if ( !isset( self::$cache )) {
            self::$cache = new \PHP\Cache( 'integer', 'WordPress\Sites\Models\SiteSpec' );
        }
    }
    
    
    /***************************************************************************
    *                                   MAIN
    ***************************************************************************/
    
    /**
     * Add a new site
     *
     * @param string $url     The site URL
     * @param string $title   The site title
     * @param int    $adminID User ID for the site administrator
     * @return Sites\Models\Site|null Null on failure
     */
    public static function Add( string $url, string $title, int $adminID )
    {
        // Variables
        $site = null;
        
        // Exit. Multisite is not enabled.
        if ( !is_multisite() ) {
            return $site;
        }
        
        // Exit. Invalid URL.
        if ( !\PHP\URL::IsValid( $url )) {
            return $site;
        }
        
        // Extract url properties and create site
        $url = new \PHP\URL( $url );
        $domain = $url->getDomain();
        $path   = $url->getPath();
        $siteID = wpmu_create_blog( $domain, $path, $title, $adminID );
        if ( !is_wp_error( $siteID )) {
            self::$cache->markIncomplete();
            $site = self::Get( $siteID );
        }
        
        return $site;
    }
    
    
    /**
     * Delete a site (permanent)
     *
     * Since WordPress does not allow you to delete the root site, neither do we.
     *
     * @param int $siteID The site (blog) ID to delete
     * @return bool Whether or not the site was deleted
     */
    final public static function Delete( int $siteID ): bool
    {
        // Variables
        $isDeleted = false;
        $siteID   = static::SanitizeID( $siteID );
        
        // Try to delete the site
        if (
            is_multisite()                &&
            ( self::INVALID !== $siteID ) &&
            ( self::ALL     !== $siteID ) &&
            ( 1             !== $siteID )
        ) {
            // Include WordPress multisite functions before attempting to
            // delete the site
            require_once(ABSPATH . 'wp-admin/includes/ms.php');
            
            // Delete the site
            wpmu_delete_blog( $siteID, true );
            self::$cache->remove( $siteID );
            $isDeleted = true;
        }
        return $isDeleted;
    }
    
    
    /**
     * Retrieve site(s)
     *
     * @param int $siteID The site ID, ALL, or CURRENT
     * @return Sites\Models\Site|ReadOnlyDictionarySpec
     */
    public static function Get( int $siteID = self::ALL )
    {
        if ( self::ALL === $siteID ) {
            return self::getAll();
        }
        else {
            return self::getSingle( $siteID );
        }
    }
    
    
    /**
     * Retrieve the current site object
     *
     * @return Sites\Models\Site
     */
    final public static function GetCurrent(): Sites\Models\Site
    {
        return self::Get( self::GetCurrentID() );
    }
    
    
    /**
     * Retrieve the current site ID
     *
     * @return int
     */
    final public static function GetCurrentID(): int
    {
        return get_current_blog_id();
    }
    
    
    /***************************************************************************
    *                     SITE ID SANITIZATION / VALIDATION
    ***************************************************************************/
    
    /**
     * Is the given site / pseudo ID valid?
     *
     * @param int $siteID The site (blog) ID to evaluate
     * @return bool
     */
    final public static function IsValidID( int $siteID ): bool
    {
        return self::INVALID !== static::SanitizeID( $siteID );
    }
    
    
    /**
     * Sanitize the site ID, always returning 1) the actual site ID, 2) ALL, or
     * 3) INVALID.
     *
     * ALL implicitly resolves to the current site ID if on a single-site
     * install. The choice to do this should be self-evident, but, if not, think
     * of it this way: the context for "all sites" is actually the current site
     * context, since there is no global context in which to execute. In fact,
     * attempting to execute multi-site procedures will always bomb if on a
     * single-site install. If a user wants to retrieve users, plugins, themes,
     * etc. for all sites, the context should always resolve to the current site
     * in order to correctly execute.
     *
     * Register all pseudo-IDs here
     *
     * @param int $siteID The site ID or pseudo-site ID
     * @return int The corresponding site ID; ALL, or INVALID
     */
    public static function SanitizeID( int $siteID ): int
    {
        // Resolve CURRENT pseudo identifier to the current site ID
        if ( self::CURRENT === $siteID ) {
            $siteID = self::GetCurrentID();
        }
        
        // Convert ALL to the current site ID if on a single-site install
        elseif ( self::ALL === $siteID ) {
            if ( !is_multisite() ) {
                $siteID = self::GetCurrentID();
            }
        }
        
        // Given an invalid site ID
        elseif (( $siteID < 0 ) || !self::getAll()->hasIndex( $siteID )) {
            $siteID = self::INVALID;
        }
        return $siteID;
    }
    
    
    /***************************************************************************
    *                              SITE SWITCHING
    ***************************************************************************/
    
    /**
     * Switch to different site context
     *
     * @param int $siteID Site (blog) ID to switch to
     */
    final public static function SwitchTo( int $siteID )
    {
        $siteID = self::SanitizeID( $siteID );
        if (
            is_multisite()                &&
            ( self::INVALID !== $siteID ) &&
            ( self::ALL     !== $siteID )
        ) {
            switch_to_blog( $siteID );
        }
    }
    
    
    /**
     * Switch back to the prior site context
     */
    final public static function SwitchBack()
    {
        if ( is_multisite() ) {
            restore_current_blog();
        }
    }
    
    
    /***************************************************************************
    *                               SUB-ROUTINES
    ***************************************************************************/
    
    
    /**
     * Retrieve single site
     *
     * @param int $siteID The site ID to lookup
     * @return Sites\Models\Site
     */
    private static function getSingle( int $siteID ): Sites\Models\Site
    {
        $siteID = static::SanitizeID( $siteID );
        if ( self::INVALID === $siteID ) {
            throw new \Exception( "Cannot retrieve site: the site ID does not exist" );
        }
        return self::getAll()->get( $siteID );
    }
    
    
    /**
     * Retrieve all sites
     *
     * @return ReadOnlyDictionarySpec
     */
    private static function getAll(): ReadOnlyDictionarySpec
    {
        
        // Lookup sites
        if ( !self::$cache->isComplete() ) {
            
            // Retrieve sites from the multisite setup
            if ( is_multisite() ) {
                $wp_sites = get_sites();
                foreach ( $wp_sites as $wp_site ) {
                    $siteID = ( int ) $wp_site->blog_id;
                    $site = Sites\Models::Create( $siteID );
                    self::$cache->add( $siteID, $site );
                }
            }
            
            // Retrieve site from default, non-multisite setup
            else {
                $site = Sites\Models::Create( 1 );
                self::$cache->add( 1, $site );
            }
            
            // Mark the cache complete
            self::$cache->markComplete();
        }
        
        // Read sites from cache
        return new ReadOnlyDictionary( self::$cache );
    }
}
Sites::Initialize();
