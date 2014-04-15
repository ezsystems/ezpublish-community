<?php
/**
 * File containing the ManagerInterface.
 *
 * This class contains ManagerInterface which will be the standard for every
 * object manager
 *
 * @copyright Copyright (C) 1999-2014 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace EzSystems\BehatBundle\ContentManager;

use eZ\Publish\API\Repository\Values\ValueObject;

/**
 * ManagerInterface
 */
interface ManagerInterface
{
    /**
     * Verify that an object with the specified identifier exsits
     *
     * @param string $identifier This is what identifies the object, can be a URL, an unique identifier
     *
     * @return boolean True if the object exists
     */
    public function objectExists( $identifier );

    /**
     * Creates an object with dummy data (all possible fields) with the passed identifier
     *
     * @param string $identifier This is what identifies the object, can be a URL, an unique identifier
     *
     * @return eZ\Publish\API\Repository\Values\ValueObject Returns the object that it creates
     */
    public function createDummyObject( $identifier );

    /**
     * Creates an object with the specified fields and add the not defined
     * required fields
     *
     * @param array $fields Any kind of definitions/fields that are needed to create the expected object
     *
     * @return eZ\Publish\API\Repository\Values\ValueObject Returns the object that it creates
     */
    public function createObject( array $fields = null );

    /**
     * Remove the object with $identifier
     *
     * @param string $identifier This is what identifies the object, can be a URL, an unique identifier
     */
    public function removeObject( $identifier );

    /**
     * Remove all objects (or inside the $limitation)
     *
     * @param array $limitations Specify the limitations on the complete removal
     */
    public function removeAllObjects( array $limitations = null );

    /**
     * Depending on the passed definitions there should
     *
     * @param string $search     This is what should be searched
     * @param array $definitions Any kind of definitions that are needed to create the expected object
     *
     * @return int Returns the total of objects
     */
    public function countObjects( $search, array $definitions = null );

    /**
     * Retrieve complete list of objects
     *
     * @param array $limitations Specify the limitations on the complete list
     *
     * @return mixed[] Returns the object
     */
    public function getObjectList( array $limitations = null );

    /**
     * Retrieve the object with the identifier
     *
     * @param string $identifier This is what identifies the object, can be a URL, an unique identifier
     *
     * @throws eZ\Publish\Core\Base\Exceptions\InvalidArgumentValue If the $identifier is not of an expected type
     *
     * @return mixed Returns the object
     */
    public function getObject( $identifier );

    /**
     * Update an object with the specified definitions
     *
     * @param string $identifier This is what identifies the object, can be a URL, an unique identifier
     * @param array $fields      Any kind of definitions that are needed to update the expected object
     *
     * @return mixed Returns the object
     */
    public function updateObject( $identifier, array $fields = null );

    /**
     * Compare the expected data with the actual inside the object
     *
     * @param array|eZ\Publish\API\Repository\Values\ValueObject  $expectedData All data should be in an associative array
     * @param eZ\Publish\API\Repository\Values\ValueObject $actualObject The object of the type of the ContentManager
     *
     * @return mixed True if the object has the data, false other wise
     */
    public function compareDataWithObject( $expectedData, ValueObject $actualObject );
}
