<?php
namespace WordPress\Plugins\Models;

/**
 * Represents a single WordPress plugin
 */
class Plugin extends _Plugin
{
    
    /**
     * This plugin's unique identifier
     *
     * @var string
     */
    private $id;
    
    /**
     * Instantiate a new Plugin instance
     *
     * @param string $id This plugin's unique identifier
     */
    final public function __construct( string $id )
    {
        $this->id = $id;
    }
    
    
    final public function getID()
    {
        return $this->id;
    }
}