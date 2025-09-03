<?php

namespace App\Controller\Traits;

use App\Entity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\Collections\ArrayCollection;
use App\Util\EntityHelper;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// ag: A helper Class only meant for use in controllers to encapsulate logic related to entity fetching and response rendering. Handling errors by returning a view to the user (e.g., “record not found,” “database error”). 

trait EntityActions
{
    private \Exception $lastException;
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
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

    /** 
     * create a new entity instance/record
     *
     * @param  array $props key/value pair of entity properties to apply to the new record
     * @param  object|null $record reference to new record
     * @param  EntityHelper $eh
     * @param  bool $discardId whether to discard a pre-set ID/primary key from the $record entity o4 $entityProps passed properties (true), or leave them (false)
     * @return bool
     */
    protected function _create(?array $entityProps = [], mixed &$record = null, ?EntityHelper $eh = null, ?bool $discardId = false): bool
    {
        try {
            //$eh->setEntity($record);

            $record->fill($entityProps, $eh, true); // fill is defined in App\Entity\Traits\Fillable

            //dd($record);

            // if specified, discard the pre-set ID for the record
            if ($discardId && $record->{$record->idField} !== null) {
                $record->{$record->idField} = null;
            } // end if

            $this->em->persist($record); // prepare the record for writing
            $this->em->flush(); // commit writing the record

            // success
            return true;
        } catch (\Exception $e) {
            $this->lastException = $e;
            return false;
        } // end try/catch
    } // end function _create

    /**
     * update an existing entity instance/record
     *
     * @param  array $props key/value pair of entity properties to apply to the new record
     * @param  object $record reference to existing record
     * @param  EntityHelper $eh
     * @return bool
     */
    protected function _update(?array $entityProps = [], mixed &$record = null, ?EntityHelper $eh = null): bool
    {
        try {
            //$eh->setEntity($record);

            $record->fill($entityProps, $eh); // fill is defined in App\Entity\Traits\Fillable

            $this->em->persist($record); // prepare the record for writing
            $this->em->flush(); // commit writing the record

            // success
            return true;
        } catch (\Exception $e) {
            $this->lastException = $e;
            return false;
        } // end try/catch
    } // end function _update

    /**
     * save (create or update) an entity instance/record, with desired information already set in the passed object
     *
     * @param  object $record record to be saved
     * @return bool
     */
    protected function _save(&$record): bool
    {
        try {
            $this->em->persist($record); // prepare the record for writing
            $this->em->flush(); // commit writing the record

            // success
            return true;
        } catch (\Exception $e) {
            $this->lastException = $e;
            return false;
        } // end try/catch
    } // end function _save

    /**
     * delete a single entity instance/record
     *
     * @param  object $record reference to existing record
     * @return bool
     */
    protected function _delete(&$record): bool
    {
        try {
            $this->em->remove($record); // prepare to remove the record
            $this->em->flush(); // commit removing the record

            // success
            return true;
        } catch (\Exception $e) {
            $this->lastException = $e;
            return false;
        } // end try/catch
    } // end function _delete

    /**
     * get the count of a collection of fetched entities
     *
     * @param string $filterMethod filter method to call on resulting collection
     * @param mixed $filterParam parameter to pass to filter method
     * @param string $entityClass entity class to fetch from
     * @return int|Response
     */
    public function _count(?string $filterMethod = null, $filterParam = [], ?string $entityClass = null)
    {
        if (empty($entityClass)) $entityClass = $this->entityClass;

        try {
            $query = $this->em->createQuery("SELECT x FROM {$entityClass} x");
            $result = new ArrayCollection($query->getResult());
            $records = empty($filterMethod) ? $result : $result->filter(function ($record) use ($filterMethod, $filterParam) {
                return $record->$filterMethod($filterParam);
            });
        } catch (\Doctrine\ORM\EntityNotFoundException $e) {
            $this->lastException = $e;
            throw new NotFoundHttpException('Record not found: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->lastException = $e;
            throw new NotFoundHttpException('Could not load entity: ' . $e->getMessage());
        } // end try/catch

        return count($records);
    } // end function _count

    /**
     * alias of self::_count with parameters reversed
     *
     * @param string $entityClass entity class to fetch from
     * @param mixed $filterParam parameter to pass to filter method
     * @param string $filterMethod filter method to call on resulting collection
     * @return int|Response
     */
    protected function _countFrom(?string $entityClass = null, $filterParam = [], ?string $filterMethod = null)
    {
        return $this->_count($filterMethod, $filterParam, $entityClass);
    } // end function _countFrom

    /**
     * get the last exception thrown, or a new/blank exception if none
     * 
     * @return \Exception
     */
    public function getLastException(): \Exception
    {
        return is_object($this->lastException) && $this->lastException instanceof \Exception ? $this->lastException : new \Exception;
    } // end function getLastException

    /**
     * get the message from the last exception thrown
     * 
     * @return string|null exception message, if available
     */
    public function getLastExceptionMessage(): ?string
    {
        return is_object($this->getLastException()) && $this->getLastException() instanceof \Exception ? $this->getLastException()->getMessage() : null;
    } // end function getLastException
}
