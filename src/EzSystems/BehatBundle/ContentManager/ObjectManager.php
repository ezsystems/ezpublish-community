<?php
/**
 * File containing the ObjectManager abstract class.
 *
 * This class contains the generic functions for manipulating the objects
 *
 * @copyright Copyright (C) 1999-2014 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace EzSystems\BehatBundle\ContentManager;

use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\Values\ValueObject;
use eZ\Publish\API\Repository\Repository\Values\User\User;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentValue;

/**
 * ObjectManager
 *
 * This have already some redundant methods for all the object managers
 */
abstract class ObjectManager
{
    /**
     * Constants to the auto complete
     */
    const AC_ALPHA                  = 'ALPHA';
    const AC_ALPHA_SPACES           = 'ALPHA_SPACES';
    const AC_NUMERIC                = 'NUMERIC';
    const AC_ALPHA_NUMERIC          = 'ALPHA_NUMERIC';
    const AC_ALPHA_NUMERIC_SPACES   = 'ALPHA_NUMERIC_SPACES';
    const AC_INTEGER                = 'INTEGER';
    const AC_FLOAT                  = 'FLOAT';
    const AC_PRICE                  = 'PRICE';
    const AC_XML                    = 'XML';
    const AC_EMAIL                  = 'EMAIL';
    const AC_PATH                   = 'PATH';
    const AC_DATE                   = 'DATE';
    const AC_DATETIME               = 'DATETIME';
    const AC_TIMESTAMP              = 'TIMESTAMP';
    const AC_UNIQUE                 = 'UNIQUE';

    /**
     * Constants to the auto complete validation
     */
    const VALIDATION_MIN            = 'MIN';
    const VALIDATION_MAX            = 'MAX';
    const VALIDATION_UNIQUE         = 'UNIQUE';
    const VALIDATION_LOWERCASE      = 'LOWERCASE';
    const VALIDATION_UPPERCASE      = 'UPPERCASE';
    const VALIDATION_IS_USER_ID     = 'USERID';
    const VALIDATION_YESTERDAY      = 'YESTERDAY';

    /**
     * @var \eZ\Publish\API\Repository\Repository
     */
    protected $repository;

    /**
     * It should contain all possible fields for the object and how to set them up
     *
     * This var should be set on children
     *
     * Example 1:
     * <code>
     *  $fieldValueGenerator = array(
     *      'fieldN' => array(
     *          self::AC_TYPE_OF_DATA_TO_BE_GENERATED => array(
     *              // validation array here
     *          ),
     *      ),
     *      ...
     *  );
     * </code>
     *
     * Example 2:
     * <code>
     *  $fieldValueGenerator = array(
     *      'fieldN' => array(
     *          'type' => self::AC_TYPE_OF_DATA_TO_BE_GENERATED
     *          'validations' => array(
     *               // validation array here
     *          ),
     *      ),
     *      ...
     *  );
     * </code>
     *
     * IMPORTANT:
     *      Since the creation of the $fieldValueGenerator array and the $fieldValueGenerator
     *      method are made on the childs, the only requirement is that they
     *      can understand each other, these are only examples of possible
     *      approaches
     *
     * @see ObjectManager::generateValue() for validation array information
     *
     * @var array
     */
    protected $fieldValueGenerator;

    /**
     * Array with the fields that need to be filled for the object creation,
     * the fields names/definitions should match the ContentMangager::$fieldValueGenerator
     *
     * This var should be set on children
     *
     * @var array
     */
    protected $requiredFields;

    /**
     * This is the var will contain all objects created by the object manager
     * that will be removed after testing by __destruct
     *
     * @var array
     */
    protected $createdObjects;

    /**
     * Since most of times we need an admin to create content, we need to set the
     * actual user as the admin, but we don't want to lose the actual user
     *
     * @var \eZ\Publish\API\Repository\Repository\Values\User\User
     */
    protected $lastUserSet;

    /**
     * @param \eZ\Publish\API\Repository\Repository $repository
     */
    public function __construct( Repository $repository)
    {
        $this->repository = $repository;
        $this->createdObjects = array();
    }

    /**
     * In most transactions to create an object it need to be an user with permissions
     * so the best is to set it to admin, and at the end set it back to the last user
     *
     * IMPORTANT:
     *      If using API for testing, and the user isn't set back to the previous
     *      user at the end of the action, it can have unexpected results on the
     *      tests
     */
    public function setAdmin()
    {
        // if there is an set user is because the actual user is an admin
        // so skip the set user
        if ( !empty( $this->lastUserSet ) )
        {
            return;
        }

        $this->lastUserSet = $this->repository->getCurrentUser();
        $this->repository->setCurrentUser(
            $this->repository->getUserService()->loadUserByLogin( 'admin' )
        );
    }

    /**
     * Sets the user to the last one, should only be used after the setAdmin()
     *
     * @see ObjectManager::setAdmin()
     */
    public function setLastUser()
    {
        if ( $this->lastUserSet instanceof User )
        {
            $this->repository->setCurrentUser(
                $this->lastUserSet
            );
            unset( $this->lastUserSet );
        }
    }

    /**
     * Generate data for the auto complete
     *
     * @param string $type The constant value to the expected data generated
     * @param array  $validations Validations that affect the generated data
     *
     * $validations array example:
     * <code>
     *  $validations = array(
     *      self::VALIDATION_LOWERCASE,
     *      self::VALIDATION_MIN => 10
     *  );
     * </code>
     *
     * IMPORTANT:
     *      The validations should always be part of the array if there are no
     *      settings for them
     *          (ex: $validations = array( self::VALIDATION_UPPERCASE )),
     *      or an key to an definition or an array of definitions
     *          (ex: $validations = array( self::VALIDATION_MIN => <value> ))
     *          (ex: $validations = array( seld::VALIDATION_UNIQUE => array( <value1>, ... ))
     *
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentValue If the $type doesn't match any option
     *
     * @return mixed The generated data
     */
    protected function generateValue( $type, array $validations = null )
    {
        // if it must be unique then send to the unique method and remove the
        // unique element form the array
        if (
            array_key_exists( self::AC_UNIQUE, $validations )
            || array_search( self::AC_UNIQUE, $validations ) !== false
        )
        {
            // remove the UNIQUE if it is still present
            if ( array_search( self::AC_UNIQUE, $validations ) !== false )
            {
                unset( $validations[array_search( self::AC_UNIQUE, $validations )] );
            }

            // attempt to generate an unique
            return $this->generateUnique( $type, $validations );
        }

        // check minimum and maximum existence ( since they will be widely used
        // on many data generators
        $min = empty( $validations[self::VALIDATION_MIN] ) ? 0 : $validations[self::VALIDATION_MIN];
        $max = empty( $validations[self::VALIDATION_MAX] ) ? -1 : $validations[self::VALIDATION_MAX];

        switch( strtoupper( $type ) )
        {
            case self::AC_ALPHA:
                return Utils::createRandomStringFromString(
                    Utils::getCharactersList( array( 'alpha' ), true ),
                    $min,
                    $max
                );

            case self::AC_ALPHA_SPACES:
                return Utils::createRandomStringFromString(
                    Utils::getCharactersList( array( 'alpha', 'spaces' ), true ),
                    $min,
                    $max
                );

            case self::AC_NUMERIC:
                return Utils::createRandomStringFromString(
                    Utils::getCharactersList( array( 'numeric' ), true ),
                    $min,
                    $max
                );

            case self::AC_ALPHA_NUMERIC:
                return Utils::createRandomStringFromString(
                    Utils::getCharactersList( array( 'alpha', 'numeric' ), true ),
                    $min,
                    $max
                );

            case self::AC_ALPHA_NUMERIC_SPACES:
                return Utils::createRandomStringFromString(
                    Utils::getCharactersList( array( 'alpha', 'numeric', 'spaces' ), true ),
                    $min,
                    $max
                );

            case self::AC_FLOAT:
                $value = mt_rand() / mt_getrandmax();
                if ( !empty( $min ) && $value < $min )
                {
                    $value += $min;
                }

                if ( !empty( $max ) && $value > $max )
                {
                    $value = $value - ( ( $max - $min ) / 2 );
                }

                return $value;

            case self::AC_INTEGER:
                return rand(
                    $min,
                    ( $max < 0 ) ? $min + rand() : $max
                );

            case self::AC_PRICE:
                return rand(
                    $min * 100,
                    ( $max < 0 ) ? $min + rand() * 100 : $max * 100
                ) / 100;

            // if nothing matches it can be a CUSTOM value or an invalid value
            default:
                $value = $this->generateCustomValue( $type, $validations );
                if ( $value === false )
                {
                    throw new InvalidArgumentValue( 'generate', $type );
                }
                return $value;
        }
    }

    /**
     * Run generate data until it finds an unique value
     *
     * @param string $type The constant value to the expected data generated
     * @param array  $validations Validations that affect the generated data
     *
     * @return mixed The unique generated data
     */
    protected function generateUnique( $type, array $validations = null )
    {
        $definitions = null;
        // if there is a field/definition associated get it and unset from array
        if ( !empty( $validations[self::AC_UNIQUE] ) )
        {
            $definitions = $validations[self::AC_UNIQUE];
            unset( $validations[self::AC_UNIQUE] );
        }

        $isUnique = false;
        while ( !$isUnique )
        {
            $value = $this->generateValue( $type, $validations );
            $isUnique = $this->isUnique( $value, $definitions );
        }

        return $value;
    }

    /**
     * Executes the verification if the generated value is unique
     *
     * @param mixed $value Value generated
     * @param array $definitions Definitions for cases where there might be several uniques on the same object
     *
     * @return boolean True if unique, false otherwise
     */
    abstract protected function isUnique( $value, array $definitions = null );

    /**
     * Is possible to generate any kind of data made on the children with the
     * extension of this
     *
     * @param mixed $type Custom type defined on children
     * @param array $validations Any possible validation for the $type
     *
     * @return false|mixed
     */
    protected function generateCustomValue( $type, array $validations = null )
    {
        return false;
    }

    /**
     * Destroy/remove/delete all created objects
     */
    public function __destruct()
    {
        $this->setAdmin();
        foreach ( $this->createdObjects as $object )
        {
            $this->destroyObject( $object );
        }
        $this->setLastUser();
    }

    /**
     * This is used by the __destruct() function to delete/remove all the objects
     * that were created for testing
     *
     * @param object $object Object that should be destroyed/removed
     */
    abstract protected function destroyObject( $object );
}
