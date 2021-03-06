<?php

namespace Tellaw\SunshineAdminBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormBuilder;
use Tellaw\SunshineAdminBundle\Interfaces\ConfigurationReaderServiceInterface;
use Doctrine\DBAL\Types\JsonArrayType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class CrudService
{

    /**
     *
     * Alias used for QueryBuilder
     *
     * @var string
     */
    private $alias = 'l';

    /**
     * Entity manager
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * Entity Service
     * @var EntityService
     */
    private $entityService;

    /**
     * CrudService constructor.
     * @param EntityManagerInterface $em
     * @param EntityService $entityService
     */
    public function __construct(
        EntityManagerInterface $em,
        EntityService $entityService
    ) {
        $this->em = $em;
        $this->entityService = $entityService;
    }

    public function getTotalElementsInTable ( $entityName ) {

        $baseConfiguration = $this->entityService->getConfiguration( $entityName );

        $qb = $this->em->createQueryBuilder();
        $qb->select('COUNT(l)')->from($baseConfiguration["configuration"]["class"], 'l');
        return $qb->getQuery()->getSingleScalarResult();

    }

    /**
     * Return the total count of an entity
     * @param $entityName
     */
    public function getCountEntityElements (  $entityName, $orderCol, $orderDir, $start, $length, $searchValue ) {


        $result = $this->getEntityList(  $entityName, $orderCol, $orderDir, $start, $length, $searchValue, false );

        return count ($result);
    }

    /**
     * Get single Entity
     *
     * @param $entityName
     * @param $entityId
     * @return array
     */
    public function getEntity($entityName, $entityId)
    {
        $baseConfiguration = $this->entityService->getConfiguration( $entityName );
        $repository = $this->em->getRepository($baseConfiguration["configuration"]["class"]);

        $result = $repository->findOneById($entityId);

        return $result;
    }

    /**
     * Get an entity list
     * @param $entityName
     * @return array
     * @internal param Context $context
     * @internal param $configuration
     */
    public function getEntityList( $entityName, $orderCol, $orderDir, $start, $length, $searchValue, $enablePagination = true )
    {

        $listConfiguration = $this->entityService->getListConfiguration( $entityName );
        $baseConfiguration = $this->entityService->getConfiguration( $entityName );

        $qb = $this->em->createQueryBuilder();

        $qb = $this->addSelectAndJoin ( $qb, $listConfiguration, $baseConfiguration );
        $qb = $this->addPagination( $qb, $start, $length, $enablePagination );
        $qb = $this->addOrderBy ( $qb, $listConfiguration, $orderCol, $orderDir );
        $qb = $this->addSearch ( $qb, $searchValue, $listConfiguration, $baseConfiguration );


       /*
        // Filters
        $filters = $context->getFilters();
        if (!empty($filters)) {
            foreach ($filters as $key => $value) {
                $qb->andWhere($alias . '.' . $key . ' LIKE :filterValue');
                $qb->setParameter('filterValue', "%{$value}%");
            }
        }
*/
        // GET RESULT
        $result = $qb->getQuery()->getResult();

        return $result;
    }

    private function getAliasForEntity ( $property ) {
        return strtolower( $property."_" );
    }

    /**
     *
     * Add Select and Join elements to the query
     *
     * @param $qb
     * @param $listConfiguration
     * @param $baseConfiguration
     * @return mixed
     */
    private function addSelectAndJoin ( $qb, $listConfiguration, $baseConfiguration ) {

        $fields = [];
        $joins = [];

        // GET COLUMNS AS FIELDS
        foreach ($listConfiguration as $key => $item) {

            if (isset( $item["type"] ) && $item["type"] != "custom" || !isset($item["type"]) ) {

                if (key_exists('relatedClass', $item) && $item['relatedClass'] != false ) {
                    $joinField = ['class' => $item['relatedClass'], 'name' => $key];

                    // GET FOREIGN STRING FIELD TO SHOW
                    if (isset($item['toString'])) {
                        $joinField['toString'] = $item['toString'];
                    }
                    $joins[] = $joinField;
                } else {
                    $fields[] = $this->alias.".".$key;
                }
            }
        }

        // PREPARE QUERY WITH FIELDS
        $fieldsLine = implode(',', $fields);
        $qb->select($fieldsLine ? $fieldsLine : $this->alias);
        $qb->from($baseConfiguration["configuration"]["class"], $this->alias);

        // PREPARE QUERY WITH JOINED FIELDS
        foreach ($joins as $k => $join) {

            $joinAlias = $this->getAliasForEntity( $join['name'] );
            $qb->innerJoin($this->alias.'.'.$join['name'], $joinAlias);
            $joinField = isset($join['toString']) ? $join['toString'] : 'id';

            $qb->addSelect($joinAlias.'.'.$joinField.' as '.$join['name']);
        }

        return $qb;

    }

    /**
     * Add the pagination to the query
     *
     * @param $qb
     * @param $start
     * @param $length
     * @param $enablePagination
     * @return mixed
     */
    private function addPagination ( $qb, $start, $length, $enablePagination ) {

        // PREPARE QUERY FOR PAGINATION AND ORDER
        if ($enablePagination) {
            $qb->setFirstResult($start);
            $qb->setMaxResults($length);
        }

        return $qb;
    }

    /**
     * Add orderBy to the query
     *
     * @param $qb
     * @param $listConfiguration
     * @param $orderCol
     * @param $orderDir
     * @return mixed
     */
    private function addOrderBy ( $qb, $listConfiguration, $orderCol, $orderDir ) {

        //$listConfiguration[$orderCol]
        $keys = array_keys( $listConfiguration );
        if ( $this->isRelatedObject( $listConfiguration[$keys[$orderCol]]) ) {
            $joinAlias = $this->getAliasForEntity( $keys[$orderCol] );
            $qb->orderBy( $joinAlias.".".$listConfiguration[$keys[$orderCol]]["toString"] , $orderDir);
        } else {
            $qb->orderBy( $this->alias.".".$keys[$orderCol] , $orderDir);
        }


        return $qb;
    }

    private function isRelatedObject ( $item ) {
        if (key_exists('relatedClass', $item ) && $item["relatedClass"] != false ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Add search option to the query
     *
     * @param $qb
     * @param $searchValue
     * @param $baseConfiguration
     * @return mixed
     */
    private function addSearch ( $qb, $searchValue, $listConfiguration, $baseConfiguration ) {
        // PREPARE QUERY FOR PARAM SEARCH
        if ($searchValue != "" && isset($baseConfiguration["list"]["search"]) ) {

            $searchConfig = $baseConfiguration["list"]["search"];

            $searchParams = [];
            foreach ($searchConfig as $key => $item) {

                if ( $this->isRelatedObject( $listConfiguration[$key]) ) {
                    $joinAlias = $this->getAliasForEntity( $key );
                    $qb->orWhere(' '.$joinAlias.'.'.$listConfiguration[$key]["toString"].' LIKE :search');
                    $searchParams[] = " ".$joinAlias.".".$listConfiguration[$key]["toString"]." LIKE :searchParam";
                } else {
                    $qb->orWhere($this->alias.'.'.$key.' LIKE :search');
                    $searchParams[] = " l.".$key." LIKE :searchParam";
                }

            }

            $qb->setParameter('search', "%{$searchValue}%");
        }
        return $qb;
    }

    /**
     *
     * Method used to generate fields in form
     *
     * @param FormBuilder $formBuilder
     * @param $formConfiguration
     * @return mixed
     * @throws \Exception
     *
     */
    public function buildFormFields ( FormBuilder $formBuilder, $formConfiguration ) {

        // Class par type
        $formTypeClass = [
            'array' => TextareaType::class,
            'bigint' => TextType::class,
            'boolean' => TextType::class,
            'date' => DateType::class,
            'datetime' => DateTimeType::class,
            'datetimetz' => TextType::class,
            'email' => TextType::class,
            'float' => TextType::class,
            'guid' => TextType::class,
            'id' => TextType::class,
            'image' => TextType::class,
            'integer' => TextType::class,
            'json_array' => JsonArrayType::class,
            'object' => EntityType::class,
            'raw' => TextType::class,
            'simple_array' => TextType::class,
            'smallint' => TextType::class,
            'string' => TextType::class,
            'tel' => TextType::class,
            'text' => TextType::class,
            'time' => TextType::class,
            'toggle' => TextType::class,
            'url' => TextType::class,
        ];

        foreach ($formConfiguration as $fieldName => $field) {

            switch ( $field["type"] ) {

                case "date":

                    $fieldAttributes = array(
                        'widget' => 'single_text',
                        'input' => 'datetime',
                        'format' => 'dd/MM/yyyy',
                        'attr' => array('class' => 'date-picker')
                    );

                    if ( isset ( $field['label'] ) )
                    {
                        $fieldAttributes['label'] = $field['label'];
                    }

                    $formBuilder->add(
                        $fieldName,
                        $formTypeClass[$field['type']],
                        $fieldAttributes
                    );
                    break;

                case "datetime":

                    $fieldAttributes = array(
                        'widget' => 'single_text',
                        'input' => 'datetime',
                        'format' => 'dd/MM/yyyy hh:mm',
                        'attr' => array('class' => 'datetime-picker'),
                    );

                    if ( isset ( $field['label'] ) )
                    {
                        $fieldAttributes['label'] = $field['label'];
                    }

                    $formBuilder->add(
                        $fieldName,
                        $formTypeClass[$field['type']],
                        $fieldAttributes
                    );
                    break;

                case "object":

                    if ( !isset ( $field["relatedClass"] ) ) throw new \Exception("Object must define its related class, using relatedClass attribute or Doctrine relation on Annotation");
                    if ( !isset ( $field["toString"] ) ) throw new \Exception("Object must define a toString attribut to define the correct label to use -> field : ".$field["label"]);

                    $fieldAttributes = array(

                        // query choices from this entity
                        'class' => $field["relatedClass"],

                        // use the User.username property as the visible option string
                        'choice_label' => $field["toString"],



                        // used to render a select box, check boxes or radios
                        'multiple' => $field['multiple'],
                        'expanded' => $field['expanded'],
                    );

                    if ( isset ( $field['label'] ) )
                    {
                        $fieldAttributes['label'] = $field['label'];
                    }

                    $formBuilder->add($fieldName, EntityType::class, $fieldAttributes);
                    break;

                default:
                    $fieldAttributes = array();
                    if ( isset ( $field['label'] ) )
                    {
                        $fieldAttributes['label'] = $field['label'];
                    }
                    $formBuilder->add($fieldName, $formTypeClass[$field['type']], $fieldAttributes);
                    break;
            }

        }

        return $formBuilder;

    }

}
