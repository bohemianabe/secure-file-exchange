<?php

namespace App\Controller\Traits;

use App\Entity;
use App\Entity\Firms;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// ag: A helper Class only meant for use in controllers to encapsulate logic related to entity fetching and response rendering. Handling errors by returning a view to the user (e.g., “record not found,” “database error”). 

trait EntityActions
{
    private \Exception $lastException;
    private $em;
    private $params;

    public function __construct(EntityManagerInterface $em, #[Autowire('%firm.user_types%')] private array $firmUserTypes, ParameterBagInterface $params)
    {
        $this->em = $em;
        $this->firmUserTypes = $firmUserTypes;
        $this->params = $params;
    }

    /**
     * fetch a single entity or a collection of entities of the specified entity type, or get a new/blank entity instance
     *
     * @param string $criteriaVal criteria value to search for
     * @param string $criteriaField criteria field to search in
     * @param string $entityClass entity class to fetch from
     * @return object|Response
     */
    protected function _fetch(?string $criteriaVal = null, string $criteriaField = null, ?string $entityClass = null)
    {
        if (empty($entityClass)) $entityClass = $this->entityClass;
        if (!class_exists($entityClass)) return false;

        try {
            $record = empty($criteriaVal) && empty($criteriaField)
                ? new $entityClass
                : (empty($criteriaField)
                    ? $this->em->getRepository($entityClass)->find($criteriaVal)
                    : $this->em->getRepository($entityClass)->findBy([$criteriaField => $criteriaVal]));
        } catch (\Doctrine\ORM\EntityNotFoundException $e) {
            $this->lastException = $e;
            throw new NotFoundHttpException('Record not found: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->lastException = $e;
            throw new NotFoundHttpException('Could not load entity: ' . $e->getMessage());
        } // end try/catch

        return $record;
    } // end function _fetch

    /**
     * fetch a single entity of the specified entity type, or get a new/blank entity instance
     *
     * @param string $criteriaVal criteria value to search for
     * @param string $criteriaField criteria field to search in
     * @param string $entityClass entity class to fetch from
     * @return object|Response
     */
    protected function _fetchOne(?string $criteriaVal = null, string $criteriaField = null, ?string $entityClass = null)
    {
        if (empty($entityClass)) $entityClass = $this->entityClass;

        try {
            $record = empty($criteriaVal) ? new $entityClass
                : (empty($criteriaField)
                    ? $this->em->getRepository($entityClass)->find($criteriaVal)
                    : $this->em->getRepository($entityClass)->findOneBy([$criteriaField => $criteriaVal]));
        } catch (\Doctrine\ORM\EntityNotFoundException $e) {
            $this->lastException = $e;
            throw new NotFoundHttpException('Record not found: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->lastException = $e;
            throw new NotFoundHttpException('Could not load entity: ' . $e->getMessage());
        } // end try/catch

        return $record;
    } // end function _fetchOne

    /**
     * fetch a collection of entities of the specified entity type
     *
     * @param string $entityClass entity class to fetch from
     * @param int $limit maximum number of records to retrieve
     * @param array $order order by condition, in [field => direction] format
     * @return object|Response
     */
    protected function _fetchAll(?string $entityClass = null, ?int $limit = null, ?array $order = [])
    {
        if (empty($entityClass)) $entityClass = $this->entityClass;

        try {
            $records = empty($order) && (empty($limit) || $limit < 1)
                ? $this->em->getRepository($entityClass)->findAll()
                : $this->em->getRepository($entityClass)->findBy([], $order, $limit);
        } catch (\Doctrine\ORM\EntityNotFoundException $e) {
            $this->lastException = $e;
            throw new NotFoundHttpException('Record not found: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->lastException = $e;
            throw new NotFoundHttpException('Could not load entity: ' . $e->getMessage());
        } // end try/catch

        return $records;
    } // end function _fetchAll

    /**
     * alias of self::_fetch with parameters reversed
     *
     * @param string $entityClass entity class to fetch from
     * @param string $criteriaVal criteria value to search for
     * @param string $criteriaField criteria field to search in
     * @return object|Response
     */
    protected function _fetchFrom(?string $entityClass = null, ?string $criteriaVal = null, string $criteriaField = null)
    {
        return $this->_fetch($criteriaVal, $criteriaField, $entityClass);
    } // end function _fetchFrom

    /**
     * fetch a collection of entities of the specified entity type
     *
     * @param string $entityClass entity class to fetch from
     * @param int $limit maximum number of records to retrieve
     * @param array $order order by condition, in [field => direction] format
     * @return object|Response
     */
    protected function _fetchWhere(?string $entityClass = null, ?array $criteria = [], ?int $limit = null, ?array $order = [])
    {
        if (empty($entityClass)) $entityClass = $this->entityClass;

        try {
            $records = $this->em->getRepository($entityClass)->findBy($criteria, $order, $limit);
        } catch (\Doctrine\ORM\EntityNotFoundException $e) {
            $this->lastException = $e;
            throw new NotFoundHttpException('Record not found: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->lastException = $e;
            throw new NotFoundHttpException('Could not load entity: ' . $e->getMessage());
        } // end try/catch

        return $records;
    } // end function _fetchWhere

    /**
     * fetch a single entity of the specified entity type
     *
     * @param string $entityClass entity class to fetch from
     * @param int $limit maximum number of records to retrieve
     * @param array $order order by condition, in [field => direction] format
     * @return object|Response
     */
    protected function _fetchOneWhere(?string $entityClass = null, ?array $criteria = [], ?int $limit = null, ?array $order = [])
    {
        if (empty($entityClass)) $entityClass = $this->entityClass;

        try {
            $record = $this->em->getRepository($entityClass)->findOneBy($criteria, $order, $limit);
        } catch (\Doctrine\ORM\EntityNotFoundException $e) {
            $this->lastException = $e;
            throw new NotFoundHttpException('Record not found: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->lastException = $e;
            throw new NotFoundHttpException('Could not load entity: ' . $e->getMessage());
        } // end try/catch

        return $record;
    } // end function _fetchOneWhere

    /**
     * fetch a collection of entities matching any of the specified criteria
     *
     * @param string $entityClass entity class to fetch from
     * @param array $criteria key/value pairs of properties and values to search for
     * @param int $limit maximum number of records to retrieve
     * @return object|Response
     */
    protected function _fetchWhereAny(?string $entityClass = null, ?array $criteria = [], ?int $limit = null)
    {
        if (empty($entityClass)) $entityClass = $this->entityClass;

        try {
            $criteria = is_array(reset($criteria)) ? $criteria : [$criteria];
            $qb = $this->em->createQueryBuilder();

            $qb->select("x")->from($entityClass, "x");
            foreach ($criteria as $index => $criterion) {
                $searchField = key($criterion);
                $searchVal = $criterion[$searchField];
                $qb->orWhere($qb->expr()->like("x.{$searchField}", ":{$searchField}{$index}"))
                    ->setParameter(":{$searchField}{$index}", "%{$searchVal}%");
            } // end foreach

            $records = $qb->getQuery()->getResult();
        } catch (\Doctrine\ORM\EntityNotFoundException $e) {
            $this->lastException = $e;
            throw new NotFoundHttpException('Record not found: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->lastException = $e;
            throw new NotFoundHttpException('Could not load entity: ' . $e->getMessage());
        } // end try/catch

        return $records;
    } // end function _fetchWhereAny

    /**
     * fetch a collection of entities matching all of the specified criteria
     *
     * @param string $entityClass entity class to fetch from
     * @param array $criteria key/value pairs of properties and values to search for
     * @param int $limit maximum number of records to retrieve
     * @return object|Response
     */
    protected function _fetchWhereAll(?string $entityClass = null, ?array $criteria = [], ?int $limit = null)
    {
        if (empty($entityClass)) $entityClass = $this->entityClass;

        try {
            $criteria = is_array(reset($criteria)) ? $criteria : [$criteria];
            $qb = $this->em->createQueryBuilder();

            $qb->select("x")->from($entityClass, "x");
            if (is_array($criteria)) {
                foreach ($criteria as $index => $criterion) {
                    $searchField = key($criterion);
                    $searchVal = $criterion[$searchField];
                    $qb->andWhere($qb->expr()->like("x.{$searchField}", ":{$searchVal}"))
                        ->setParameter(":{$searchField}", "%{$searchVal}%");
                } // end foreach
            } // end if

            $records = $qb->getQuery()->getResult();
        } catch (\Doctrine\ORM\EntityNotFoundException $e) {
            $this->lastException = $e;
            throw new NotFoundHttpException('Record not found: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->lastException = $e;
            throw new NotFoundHttpException('Could not load entity: ' . $e->getMessage());
        } // end try/catch

        return $records;
    } // end function _fetchWhereAll

    /**
     * fetch a collection of entities matching all of the specified criteria within a given field, e.g. multiple strings in a text or JSON field
     *
     * @param string $entityClass entity class to fetch from
     * @param string $searchInField field to search within
     * @param array $criteria key/value pairs of properties and values to search for
     * @param int $limit maximum number of records to retrieve
     * @param array $exclcude key/value pairs of properties and values to exclude
     * @return object|Response
     */
    protected function _fetchWhereAllWithin(?string $entityClass = null, ?string $searchInField = null, ?array $criteria = [], ?int $limit = null, ?array $excludes = [])
    {
        if (empty($entityClass)) $entityClass = $this->entityClass;

        try {
            $qb = $this->em->createQueryBuilder();

            $qb->select("x")->from($entityClass, "x");
            if (is_array($criteria) && !empty(reset($criteria))) {
                foreach ($criteria as $criterion) {
                    $searchIndex = key($criterion);
                    $searchVal = $criterion[$searchIndex];
                    $qb->andWhere($qb->expr()->like("LOWER(x.{$searchInField})", "LOWER(:{$searchIndex}Val)"))
                        ->setParameter(":{$searchIndex}Val", "%{$searchIndex}%{$searchVal}%");
                } // end foreach
            } // end if

            if (is_array($excludes) && !empty(reset($excludes))) {
                $excludeIndex = 0;

                foreach ($excludes as $exclude) {
                    $excludeIndex++;
                    $qb->andWhere("LOWER(x.{$searchInField}) NOT LIKE LOWER(:exclude{$excludeIndex}Val)")
                        ->setParameter(":exclude{$excludeIndex}Val", "%{$exclude}%");
                } // end foreach
            } // end if

            $records = $qb->getQuery()->getResult();
        } catch (\Doctrine\ORM\EntityNotFoundException $e) {
            $this->lastException = $e;
            throw new NotFoundHttpException('Record not found: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->lastException = $e;
            throw new NotFoundHttpException('Could not load entity: ' . $e->getMessage());
        } // end try/catch

        return $records;
    } // end function _fetchWhereAllWithin

    //     
    // 
    //   ag: helper functions for data metrics  
    // 
    protected function _getActiveFirmMetrics()
    {
        $allFirms = $this->_fetchAll(Firms::class);
        $activeFirms = $this->_fetchWhere(Firms::class, ['active' => true]);

        $total = count($allFirms);
        $active = count($activeFirms);

        if ($total === 0) {
            return 0; // avoid division by zero
        }

        return round(($active / $total) * 100, 2);
    }

    protected function _getStoragePlanMetrics()
    {
        // Get total number of firms
        $allFirms = $this->_fetchAll(Firms::class);
        $total = count($allFirms);

        if ($total === 0) {
            return [];
        }

        // Count firms per storage plan
        $plans = [];
        foreach ($allFirms as $firm) {
            $planName = $firm->getStoragePlan()->getName(); // adjust to your entity mapping
            if (!isset($plans[$planName])) {
                $plans[$planName] = 0;
            }
            $plans[$planName]++;
        }

        // Convert counts to percentages
        foreach ($plans as $name => $count) {
            $plans[$name] = round(($count / $total) * 100, 2);
        }

        return $plans;
    }
}
