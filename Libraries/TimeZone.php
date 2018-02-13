<?php
namespace WordPress;

class TimeZone extends \DateTimeZone {
    
    //
    // CONSTANTS
    
    // Identify the type of timezone given
    const GMT_TYPE            = 1;  // '+00:00'
    const ABBREVIATION_TYPE   = 2;  // 'PST'
    const IDENTIFICATION_TYPE = 3;  // 'America/Los_Angeles'
    
    
    //
    // INSTANCE
    
    // Variables
    protected $timezone;
    protected $timezone_type;
    
    // Create new TimeZone
    public function __construct( $mixed ) {
        
        // Compensate for WordPress' malformed GMT timezones (9.5, for example)
        $isFloat = preg_match( '/([-+]{0,1})(\d+)\.(\d+)/', $mixed, $matches );
        if ( $isFloat ) {
            $operand = $matches[ 1 ];
            $operand = empty( $operand ) ? '+' : $operand;
            $hour    = $matches[ 2 ];
            $hour    = str_pad( $hour, 2, '0', STR_PAD_LEFT );
            $minutes = $matches[ 3 ];
            switch ( $minutes ) {
                case 75:
                    $minutes = '45';
                    break;
                case 5:
                    $minutes = '30';
                    break;
                default:
                    $minutes = '00';
                    break;
            }
            $mixed = "{$operand}{$hour}:{$minutes}";
        }
        
        // Create new TimeZone
        parent::__construct( $mixed );
        
        // PHP doesn't allow us to access parent members. Let's change that.
        $variables = print_r( $this, true );
        preg_match_all( '/\[(\S+)\] => (\S+)/', $variables, $variables );
        $_variable_values = array_pop( $variables );
        $_variable_names  = array_pop( $variables );
        
        // Match each variable name to its value
        $variables = [];
        foreach ( $_variable_names as $i => $name ) {
            $value = $_variable_values[ $i ];
            $this->$name = $value;
        }
    }
    
    
    //
    // CONVERSION -- cascades by order
    
    // Convert to Identifier (America/Los_Angeles)
    public function toIdentifier( $fallback = true ) {
        if ( self::IDENTIFICATION_TYPE == $this->timezone_type ) {
            return $this->format( 'e' );
        }
        elseif ( $fallback ) {
            return $this->toAbbreviation();
        }
        else {
            return NULL;
        }
    }
    
    // Convert to Abbreviation (PST)
    public function toAbbreviation( $fallback = true ) {
        if ( self::ABBREVIATION_TYPE <= $this->timezone_type ) {
            return $this->format( 'T' );
        }
        elseif ( $fallback ) {
            return $this->toGMT();
        }
        else {
            return NULL;
        }
    }
    
    // Convert to GMT (+00:00)
    public function toGMT( $fallback = true ) {
        return $this->format( 'P' );
    }
    
    // Convert to given format
    private $dateTime;
    private function format( $format ) {
        if ( !isset( $this->dateTime )) {
            $this->dateTime = new \DateTime();
            $this->dateTime->setTimeZone( $this );
        }
        return $this->dateTime->format( $format );
    }
}
?>