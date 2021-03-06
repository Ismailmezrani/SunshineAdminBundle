<?php

namespace Tellaw\SunshineAdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Content pages management
 */
class PageController extends AbstractController
{
    /**
     * Expose Page
     *
     * @Route("/page/{pageId}", name="sunshine_page")
     * @Method({"GET"})
     *
     * @return JsonResponse
     */
    public function pageAction( $pageId )
    {

        /** @var array $page */
        $page = $this->get("sunshine.pages")->getPageConfiguration($pageId);

        //$configuration = $this->get("sunshine.menu")->getConfiguration();

        return $this->renderWithTheme( "Page:index", ["page" => $page, "pageId" => $pageId] );
    }

    /**
     *
     * Show a list for an entity
     * @Route("/page/list/{entityName}", name="sunshine_page_list")
     *
     * @param Request $request
     * @param $entityName
     */
    public function listAction (Request $request, $entityName) {

        /** @var EntityService $entities */
        $entities = $this->get("sunshine.entities");
        $listConfiguration = $entities->getListConfiguration($entityName);
        $configuration = $entities->getConfiguration($entityName);

        return $this->render(
            'TellawSunshineAdminBundle:Page:list.html.twig',
            [
                "extraParameters" => array ("name" => "entityName", "value" => $entityName),
                "widget" => array ("type" => "list"),
                "formConfiguration" => $configuration,
                "fields" => $listConfiguration,
                "entityName" => $entityName,
                "entity" => $entityName,
                "pageId" => null,
            ]
        );

    }

    /**
     * Shows entity
     *
     * @Route("/page/edit/{entityName}/{id}", name="sunshine_page_edit")
     * @Route("/page/edit/{entityName}", name="sunshine_page_new")
     * @Method({"GET", "POST"})
     * @param Request $request
     * @param $entityName
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $entityName, $id = null)
    {

        /** @var EntityService $entities */
        $entities = $this->get("sunshine.entities");
        $formConfiguration = $entities->getFormConfiguration($entityName);
        $configuration = $entities->getConfiguration($entityName);

        /** @var CrudService $entities */
        $crudService = $this->get("sunshine.crud_service");
        if ($id) {
            $entity = $crudService->getEntity($entityName, $id);
        } else {
            $entity = new $configuration['configuration']['class'];
        }

        $formBuilder = $this->createFormBuilder($entity);
        $formBuilder = $crudService->buildFormFields ( $formBuilder, $formConfiguration );
        $formBuilder->add('Enregistrer', SubmitType::class);
        $form = $formBuilder->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $entity = $form->getData();
            $em = $this->get('doctrine')->getEntityManager();
            $em->persist($entity);
            $em->flush($entity);

            $request->getSession()
                ->getFlashBag()
                ->add('success', 'Enregistrement effectué.')
            ;

            return $this->redirectToRoute('sunshine_page_edit', ['entityName' => $entityName, 'id' => $id]);

        }

        return $this->render(
            'TellawSunshineAdminBundle:Page:edit.html.twig',
            [
                "form" => $form->createView(),
                "formConfiguration" => $configuration,
                "fields" => $formConfiguration,
                "entityName" => $entityName,
                "entity" => $entity,
                "pageId" => null,
            ]
        );
    }

}
