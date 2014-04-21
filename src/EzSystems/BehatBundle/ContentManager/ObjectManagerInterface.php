<?php
/**
 * File containing the ObjectManagerInterface.
 *
 * This class contains ObjectManagerInterface which will be the standard for
 * every object manager
 *
 * @copyright Copyright (C) 1999-2014 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace EzSystems\BehatBundle\ContentManager;

use eZ\Publish\API\Repository\Values\ValueObject;

/**
 * ObjectManagerInterface
 *
 * This interface should have the most basic actions to interact with every (as
 * possible) object type (just like ContentType, Roles, Content objects, etc...)
 *
 * Since this needs to be adaptable to every possible object type, there are
 * some considerations to have:
 *
 * About parameters:
 * All parameters can, and should be defined on children classes how they
 * will interact with the object manager
 *  - $identifier
 *      This should be an id, identifier, path, or any other info that can
 *      uniquely identify an object
 *  - array $fields
 *      For some (maybe most) cases a simple associative array will do the
 *      job, however, there are cases where that is not enough, just like
 *      ContentType's, which, will need to have the FieldDefinitions also,
 *      so for that specific case the array could look like:
 *      <code>
 *          $fields = array(
 *              "names" => array( "some-name" ),
 *              "identifier" => "some-identifier",
 *              "fieldDefinitions" => array(
 *                  "field1" => array(
 *                        "names" => array( "some-field-name" ),
 *                          "identifier" => "some-field-identifier",
 *                  ),
 *                  ...
 *              )
 *          );
 *      </code>
 *      But this is only an example, only to show it's possible to define a
 *      different way of interacting with the object manager, but is
 *      IMPORTANT to define/document in each class how it should look like
 *  - array $definitions
 *      All search limitations should be defined here, for instance, if
 *      searching/counting/removing contents under some path it should be
 *      defined here.
 */
interface ObjectManagerInterface
{
    /**
     * Verify that an object with the specified identifier exist
     *
     * @param string $identifier
     *
     * @return boolean True if the object exists
     */
    public function exists( $identifier );

    /**
     * Creates an object with dummy data for all possible fields and if an
     * real identifier passed, it will use it also.
     *
     * @param string $identifier
     *
     * @return \eZ\Publish\API\Repository\Values\ValueObject Object that it creates
     */
    public function createDummy( $identifier );

    /**
     * Creates an object with the passed data and add the missing required fields
     *
     * @param array $fields
     *
     * @return \eZ\Publish\API\Repository\Values\ValueObject Object that it creates
     */
    public function create( array $fields = null );

    /**
     * Remove the object with $identifier
     *
     * @param string $identifier
     */
    public function remove( $identifier );

    /**
     * Remove all objects (or inside the $limitation)
     *
     * @param array $definitions Specify the search conditions for the complete removal
     */
    public function removeAll( array $definitions = null );

    /**
     * Depending on the passed definitions it should count the total of objects
     *
     * @param string $search     This is what should be searched
     * @param array $definitions Any other search conditions needed
     *
     * @return int Total of objects
     */
    public function count( $search, array $definitions = null );

    /**
     * Retrieve complete list of objects
     *
     * @param array $definitions Search conditions for the list
     *
     * @return mixed[] Object(s)
     */
    public function getList( array $definitions = null );

    /**
     * Retrieve the object with the identifier
     *
     * @param string $identifier
     *
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentValue If the $identifier is not of an expected type
     *
     * @return mixed Object
     */
    public function get( $identifier );

    /**
     * Update an object with the specified definitions
     *
     * @param string $identifier
     * @param array $fields
     *
     * @return mixed Object
     */
    public function update( $identifier, array $fields = null );

    /**
     * Compare the expected object with the actual object
     *
     * @param \eZ\Publish\API\Repository\Values\ValueObject $expected The expected object
     * @param \eZ\Publish\API\Repository\Values\ValueObject $actual The real object
     *
     * @return mixed True if the object has the data, false other wise
     */
    public function compare( ValueObject $expected, ValueObject $actual );
}
