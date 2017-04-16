<?php
/**
 * File containing the Utils class.
 *
 * This class contains utility functions that can be used wherever.
 *
 * @copyright Copyright (C) 1999-2014 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace EzSystems\BehatBundle\ContentManager;

use eZ\Publish\API\Repository\Values\ValueObject;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentValue;

/**
 * Utils
 */
class Utils
{
    const CHAR_ALPHA        = 'ALPHA';
    const CHAR_NUMERIC      = 'NUMERICT';
    const CHAR_SPACES       = 'SPACES';
    const CHAR_PUNCTUATION  = 'PUNCTUATION';
    const CHAR_ACCENTUATION = 'ACCENTUATION';
    const CHAR_NON_WESTERN  = 'NON_WESTERN';
    const CHAR_SPECIAL      = 'SPECIAL';

    /**
     * Generates a random string with the input characters
     *
     * @param string $characters String with all possible characters
     * @param int    $min Minimum output string length
     * @param int    $max Maximum output string length
     *
     * @return string Random string
     */
    static public function createRandomStringFromString( $characters, $min = 0, $max = -1 )
    {
        if ( empty   ( $max ) || $max < 0 )
        {
            $max = $min + rand( 1, 200 );
        }
        else if ( $max < $min )
        {
            $max = $min;
        }

        $total = rand( $min, $max );
        $result = '';
        for ( $i = 0; $i < $total; $i++ )
        {
            $result .= $characters[rand( 0, strlen( $characters ) - 1 )];
        }

        return $result;
    }

    /**
     * Get all characters intended for the testing propose
     *
     * @param array $list Array with the list of characters intended
     * @param boolean $caseSensitive Add upper case characters
     *
     * @return string
     *
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentValue
     */
    static public function getCharactersList( array $list, $caseSensitive = false )
    {
        $allCharacters = array(
            self::CHAR_ALPHA         => 'abcdefghijklmnopqrstuvwxyz',
            self::CHAR_NUMERIC       => '0123456789',
            self::CHAR_SPACES        => '        ',
            self::CHAR_PUNCTUATION   => '.,:;-_!?',
            self::CHAR_ACCENTUATION  => 'áéíóúýâêîôûãñõäëïöüÿåæœçðøßşğ',
            //                           Chinese          Japanese:  kanji    hiragana          katakana          Korean            Arabic
            self::CHAR_NON_WESTERN   => '電电電熱热熱聽麥麦' . '私金魚莨煙草東京' . 'わたしきんぎょた' . 'トウキョウタバコ' . "몽골에의해침략은" . "الحاد.ظهرتجوسونمي",
            self::CHAR_SPECIAL       => '@`´~^\'"#$%&{}()[]&=«»*+ªº£§\\|<>',
        );

        // add requested characters to the string
        $characters = '';
        foreach ( $list as $charactersType )
        {
            $charactersType = strtoupper( $charactersType );
            if ( !isset( $allCharacters[$charactersType] ) )
            {
                throw new InvalidArgumentValue( 'character type', $charactersType );
            }

            $characters .= $allCharacters[$charactersType];
        }

        // if it should be case sensitive concatenation all characters but in
        // upper case
        if ( $caseSensitive )
        {
            $characters .= strtoupper( $characters );
        }

        return $characters;
    }

    /**
     * Gets an object property/field
     *
     * @param \eZ\Publish\API\Repository\Values\ValueObject $object The object with the property
     * @param string $property Name of property or field
     *
     * @return mixed
     *
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentValue If the property/field is not found
     */
    static public function getProperty( ValueObject $object, $property )
    {
        if ( !is_object( $object ) )
        {
            throw new InvalidArgumentValue( $object, 'is not an object' );
        }

        if ( property_exists( $object, $property ) )
        {
            return $object->$property;
        }
        else if ( method_exists( $object, 'setField' ) )
        {
            return $object->getField( $property );
        }
        else
        {
            throw new InvalidArgumentValue( $property, "wasn't found in '" . get_class( $object ) ."' object" );
        }
    }

    /**
     * Sets an object property/field to the intended value
     *
     * @param \eZ\Publish\API\Repository\Values\ValueObject $object The object to be updated
     * @param string $property Name of property or field
     * @param mixed  $value The value to set the property/field to
     *
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentValue If the property/field is not found
     */
    static public function setProperty( ValueObject $object, $property, $value )
    {
        if ( property_exists( $object, $property ) )
        {
            $object->$property = $value;
        }
        else if ( method_exists( $object, 'setField' ) )
        {
            $object->setField( $property, $value );
        }
        else
        {
            throw new InvalidArgumentValue( $property, "wasn't foun in '" . get_class( $object ) ."' object" );
        }
    }

    /**
     * Sets an objects properties
     *
     * @param \eZ\Publish\API\Repository\Values\ValueObject $object Object to be updated
     * @param array $values Associative array with properties => values
     */
    static public function setProperties( ValueObject $object, array $values )
    {
        foreach ( $values as $property => $value )
        {
            self::setProperty( $object, $property, $value );
        }
    }

    /**
     * Convert object into array, ie get all properties/fields of the object
     * into an array (even the protected properties)
     *
     * @param \eZ\Publish\API\Repository\Values\ValueObject $object Object to get all properties/fields
     *
     * @return array
     *
     * @todo For ContentType the object will have several fields/properties with same name (for example 'names' that will exist in every FieldDefinition)
     */
    static public function convertObjectToArray( ValueObject $object )
    {
        // clone object to ReflectionClass
        $reflectionClass = new \ReflectionClass( $object );

        // get each property/field
        $properties = array();
        foreach ( $reflectionClass->getProperties() as $reflectionProperty )
        {
            $properties[$reflectionProperty->getName()] = self::getProperty( $object, $reflectionProperty->getName() );
        }

        return $properties;
    }

    /**
     * Verifies if the identifier is an single character internal identifier or
     * a real identifier (this identifier should be used on BDD steps)
     *
     * @param string $identifier
     *
     * @return boolean
     */
    static public function isInternalIdentifier( $identifier )
    {
        return is_string( $identifier ) && $identifier >= 'A' && $identifier <= 'Z' && strlen( $identifier ) === 1;
    }
}
