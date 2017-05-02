<?php

namespace Tellaw\SunshineAdminBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Tellaw\SunshineAdminBundle\Interfaces\ConfigurationReaderServiceInterface;
use Tellaw\SunshineAdminBundle\Interfaces\ContextInterface;
use Tellaw\SunshineAdminBundle\Interfaces\ContextServiceInterface;
use Tellaw\SunshineAdminBundle\Interfaces\CrudServiceInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;
use Tellaw\SunshineAdminBundle\Services\ConfigurationReaderService;

class CrudController extends Controller
{
    /**
     * @Route("/crud/list/{entityName}/{pageStart}/{length}/{searchKey}/{filters}/{orderBy}/{orderWay}", name="sunshine_crud_list")
     * @Method({"GET", "POST"})
     */
    public function listAction( $entityName, $pageStart = 0, $length = 30, $searchKey = '', $filters = '', $orderBy = '', $orderWay = 'ASC')
    {

        // Retrieve context for entity
        /* @var $contextService ContextServiceInterface */
        $contextService = $this->get("sunshine.context_service");
        /* @var $context ContextInterface */
        $context = $contextService->getContext( $entityName );

        /** Fill context with datas */
        $context->setNbItemPerPage( $length );
        $context->setStartPage( $pageStart );
        $context->setSearchKey( $searchKey );
        $context->setFilters( $filters );
        $context->setOrderBy( $orderBy );
        $context->setOrderWay( $orderWay );

        /* @var $configurationReaderService ConfigurationReaderServiceInterface */
        $configurationReaderService = $this->get("sunshine.configuration-reader_service");
        $headers = $configurationReaderService->getHeaderForLists( $context );

        // get using the service the list of items
        /* @var $crudService CrudServiceInterface */
        $crudService = $this->get("sunshine.crud_service");
        $entityList = $crudService->getEntityList( $context, $headers );

        // Initiate Response
        $response = array ( "headers" => $headers, "context" => $context, "list" => $entityList);

        // Return them with the JSON Response Serialized
        $serializedEntity = $this->container->get('serializer')->serialize($response, 'json');
        $response = new Response();
        $response->setContent($serializedEntity);
        $response->headers->set('Content-Type', 'application/json');
        return $response;

    }

    /**
     * @Route("/crud/edit/{entityName}/{targetId}", name="sunshine_crud_edit")
     * @Route("/crud/new/{entityName}", name="sunshine_crud_new")
     * @Method({"GET", "POST"})
     */
    public function editAction( $entityName, $targetId = null) {

        // Retrieve context for entity
        /* @var $contextService ContextServiceInterface */
        $contextService = $this->get("sunshine.context_service");

        /* @var $context ContextInterface */
        $context = $contextService->getContext( $entityName );
        $context->setTargetId( $targetId );

        /* @var $configurationReaderService ConfigurationReaderServiceInterface */
        $configurationReaderService = $this->get("sunshine.configuration-reader_service");
        $headers = $configurationReaderService->getFinalConfigurationForAViewContext( $context, ConfigurationReaderService::$_VIEW_CONTEXT_FORM );

        /* @var $crudService CrudServiceInterface */
        if ( $targetId != null ) {
            $crudService = $this->get("sunshine.crud_service");
            $object = $crudService->getEntity( $context );

            // Initiate Response for Loading object
            $response = array ( "headers" => $headers, "context" => $context, "object" => $object );

        } else {
            // Initiate Response for new Objects
            $response = array ( "headers" => $headers, "context" => $context );
        }

        // Return them with the JSON Response Serialized
        $serializedEntity = $this->container->get('serializer')->serialize($response, 'json');
        $response = new Response();
        $response->setContent($serializedEntity);
        $response->headers->set('Content-Type', 'application/json');
        return $response;

    }

    /**
     * @Route("/crud/edit/{entityName}/{targetId}", name="sunshine_crud_edit")
     * @Method({"GET", "POST"})
     */
    public function deleteAction( $targetId ) {

        $crudService = $this->get("sunshine.crud_service");


    }

    public function viewAction() {

    }

}