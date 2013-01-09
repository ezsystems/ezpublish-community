<?php
/**
 * File containing the MyController class.
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace EzSystems\DemoBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use eZ\Publish\API\Repository\Values\Content\Search\SearchResult;
use \DateTime;

class DemoController extends Controller
{
    public function testAction( $contentId )
    {
        return $this->render(
            "eZDemoBundle:content:content_test.html.twig",
            array(
                "content" => $this->getRepository()->getContentService()->loadContent( $contentId )
            )
        );
    }

    public function testWithLegacyAction( $contentId )
    {
        return $this->render(
            "eZDemoBundle:content:legacy_test.html.twig",
            array(
                "title" => "eZ Publish 5",
                "subtitle" => "Welcome to the future !",
                "messageForLegacy" => "All your eZ Publish base are belong to us ;-)",
                "content" => $this->getRepository()->getContentService()->loadContent( $contentId )
            )
        );
    }

    /**
     * Renders the top menu, with cache control
     *
     * @param int $locationId
     * @param array $excludeContentTypes
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function topMenuAction( $locationId, array $excludeContentTypes = array() )
    {
        $response = new Response;
        $response->setPublic();
        $response->setMaxAge( 60 );
        $location = $this->getRepository()->getLocationService()->loadLocation( $locationId );

        $excludeCriterion = array();
        if ( !empty( $excludeContentTypes ) )
        {
            foreach ( $excludeContentTypes as $contentTypeIdentifier )
            {
                $contentType = $this->getRepository()->getContentTypeService()->loadContentTypeByIdentifier( $contentTypeIdentifier );
                $excludeCriterion[] = new Criterion\LogicalNot(
                    new Criterion\ContentTypeId( $contentType->id )
                );
            }
        }
        $criteria = array(
            new Criterion\Subtree( $location->pathString ),
            new Criterion\ParentLocationId( $locationId ),
            new Criterion\Visibility( Criterion\Visibility::VISIBLE )
        );

        if ( !empty( $excludeCriterion ) )
            $criteria[] = new Criterion\LogicalAnd( $excludeCriterion );

        $query = new Query(
            array(
                'criterion' => new Criterion\LogicalAnd(
                    $criteria
                ),
                'sortClauses' => array(
                    $this->getSortClauseFromLocation( $location )
                )
            )
        );

        $searchResult = $this->getRepository()->getSearchService()->findContent( $query );

        $locationList = array();
        if ( $searchResult instanceof SearchResult )
        {
            foreach ( $searchResult->searchHits as $searchHit )
            {
                $locationList[] = $this->getRepository()->getLocationService()->loadLocation( $searchHit->valueObject->contentInfo->mainLocationId );
            }
        }

        return $this->render(
            "eZDemoBundle::page_topmenu.html.twig",
            array(
                "locationList" => $locationList
            ),
            $response
        );
    }

    /**
     * Renders the latest content for footer, with cache control
     *
     * @param string $pathString
     * @param string $contentTypeIdentifier
     * @param int $limit
     * @param array $excludeLocations
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function latestContentAction( $pathString, $contentTypeIdentifier, $limit, array $excludeLocations = array() )
    {
        $response = new Response;
        $response->setPublic();
        $response->setMaxAge( 60 );

        $contentType = $this->getRepository()->getContentTypeService()->loadContentTypeByIdentifier( $contentTypeIdentifier );

        $excludeCriterion = array();
        if ( !empty( $excludeLocations ) )
        {
            foreach ( $excludeLocations as $locationId )
            {
                $excludeCriterion[] = new Criterion\LogicalNot(
                    new Criterion\LocationId( $locationId )
                );
            }
        }
        $criteria = array(
            new Criterion\Subtree( $pathString ),
            new Criterion\ContentTypeId( $contentType->id ),
            new Criterion\Visibility( Criterion\Visibility::VISIBLE )
        );

        if ( !empty( $excludeCriterion ) )
            $criteria[] = new Criterion\LogicalAnd( $excludeCriterion );

        $query = new Query(
            array(
                'criterion' => new Criterion\LogicalAnd(
                    $criteria
                ),
                'sortClauses' => array(
                    new SortClause\DatePublished( Query::SORT_DESC )
                )
            )
        );
        $query->limit = $limit;

        return $this->render(
            "eZDemoBundle:footer:latest_content.html.twig",
            array(
                "latestContent" => $this->getRepository()->getSearchService()->findContent( $query )
            ),
            $response
        );
    }

    public function footerAction( $locationId )
    {
        $response = new Response;
        $response->setPublic();
        $response->setMaxAge( 60 );

        $location = $this->getRepository()->getLocationService()->loadLocation( $locationId );
        $content = $this->getRepository()->getContentService()->loadContent( $location->contentId );

        return $this->render(
            "eZDemoBundle::page_footer.html.twig",
            array(
                "content" => $content
            ),
            $response
        );
    }
    
    function getSortClauseFromLocation( $location )
    {
        $constantNameMapping = array( 'SORT_FIELD_PATH' => 'LocationPath',
                                      'SORT_FIELD_PUBLISHED' => 'DatePublished',
                                      'SORT_FIELD_MODIFIED' => 'DateModified',
                                      'SORT_FIELD_DEPTH' => 'LocationDepth',
                                   // 'SORT_FIELD_CLASS_IDENTIFIER' => '',
                                   // 'SORT_FIELD_CLASS_NAME' => '',
                                      'SORT_FIELD_PRIORITY' => 'LocationPriority',
                                      'SORT_FIELD_NAME' => 'ContentName',
                                   // 'SORT_FIELD_MODIFIED_SUBNODE' => '',
                                   // 'SORT_FIELD_NODE_ID' => '',
                                      'SORT_FIELD_CONTENTOBJECT_ID' => 'ContentId',
                                    );
        
        $reflection = new \ReflectionClass($location);
        $locationConstants = $reflection->getConstants();
       
        if( $location->sortField && is_array( $locationConstants ) )
        {
            foreach( $locationConstants as $constantName => $val )
            {
                if( strstr( $constantName, 'SORT_FIELD_') &&  $val == $location->sortField )
                {
                    if( array_key_exists( $constantName, $constantNameMapping) )
                    {
                        $sortClause = "eZ\Publish\API\Repository\Values\Content\Query\SortCLause\\". $constantNameMapping[$constantName];
                        break;
                    }
                    else
                    {
                        $sortClause = "eZ\Publish\API\Repository\Values\Content\Query\SortCLause\DatePublished";
                        break;
                    }
                }
            }
        }
        else
        {
            $sortClause = "eZ\Publish\API\Repository\Values\Content\Query\SortCLause\DatePublished";
        }
        
        if( $location->sortOrder == Location::SORT_ORDER_DESC )
            $sortOrder = Query::SORT_DESC;
        else
            $sortOrder = Query::SORT_ASC;
                
               var_dump($sortClause, new $sortClause( $sortOrder ));
               
        return new $sortClause( $sortOrder );
    }
}
