<?php
/**
 * Notice repository.
 */
namespace Repository;
use Doctrine\DBAL\Connection;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineDbalAdapter;
/**
 * Class OfferRepository.
 *
 * @package Repository
 */
class OfferRepository
{
    /**
     * Number of items per page.
     *
     * const int NUM_ITEMS
     */
    const NUM_ITEMS = 2;
    /**
     * Doctrine DBAL connection.
     *
     * @var \Doctrine\DBAL\Connection $db
     */
    protected $db;
    /**
     * OfferRepository constructor.
     *
     * @param \Doctrine\DBAL\Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }
    /**
     * Fetch all records.
     *
     * @return array Result
     */
    public function findAllPaginated($page, $table)
    {
        $countQueryBuilderModifier = function ($queryBuilder) {
            $queryBuilder->select('COUNT(DISTINCT id) AS total_results')
                ->setMaxResults(1);
        };
        $adapter = new DoctrineDbalAdapter($this->queryAll($table), $countQueryBuilderModifier);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage(self::NUM_ITEMS);
        $pagerfanta->setCurrentPage($page);
        return $pagerfanta;
    }
    public function findAll($table)
    {
        $queryBuilder = $this->queryAll($table);
        return $queryBuilder->execute()->fetchAll();
    }
    /**
     * Find one record.
     *
     * @param string $id Element id
     *
     * @return array|mixed Result
     */
    public function findOneById($id, $table)
    {
        $queryBuilder = $this->queryAll($table)
            ->where('id = :id')
            ->setParameter(':id', $id);
        $result = $queryBuilder->execute()->fetch();
        return !$result ? [] : $result;
    }
    /**
     * Save record.
     *
     * @param array $offer Offer
     *
     * @return boolean Result
     */
    public function save($object)
    {
        $this->db->beginTransaction();
        try {
            $currentDateTime = new \DateTime();
            $object['created_at'] = $currentDateTime->format('Y-m-d H:i:s');
            $cityName = $object['cities_id'];
            unset($object['cities_id']);
            $cityId = $this->findCityId($cityName);
            if ($cityId) {
                $object['cities_id'] = $cityId;
            } else {
                $this->addCity($cityName);
                $cityId = $this->findCityId($cityName);
                $object['cities_id'] = $cityId;
            }
            if (isset($object['id']) && ctype_digit((string)$object['id'])) {
                // update record
                $id = $object['id'];
                unset($object['id']);
                $this->db->update('offers', $object, ['id' => $id]);
            } else {
                // add new record
                $this->db->insert('offers', $object);
            }
            $this->db->commit();
        } catch (DBALException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    /**
     * Remove record.
     *
     * @param array $tag Tag
     *
     * @return boolean Result
     */
    public function delete($object, $table)
    {
        return $this->db->delete($table, ['id' => $object['id']]);
    }
    public function findMatchingOffers($match, $table)
    {
        $cityId = $this->findCityId($match['cities_id']);
        $match['cities_id'] = $cityId;
        $queryBuilder = $this->queryAll($table)
            ->where('offer_types_id = :offer_types_id',
                'property_types_id = :property_types_id', 'cities_id = :cities_id')
            ->setParameter(':offer_types_id', $match['offer_types_id'])
            ->setParameter(':property_types_id', $match['property_types_id'])
            ->setParameter(':cities_id', $match['cities_id']);
        $result = $queryBuilder->execute()->fetch();
        var_dump($result);
        return !$result ? [] : $result;
    }
    /**
     * Query all records.
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder Result
     */
    protected function queryAll($table)
    {
        $queryBuilder = $this->db->createQueryBuilder();
        return $queryBuilder->select('*')
            ->from($table);
    }
    protected function findCityId($cityName)
    {
        $queryBuilder = $this->db->createQueryBuilder()
            ->select('id')
            ->from('cities')
            ->where('name = :name')
            ->setParameter(':name', $cityName, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetchAll();
        $result = isset($result) ? array_column($result, 'id') : [];
        return current($result);
    }
    protected function addCity($cityName){
        $this->db->insert(
            'cities',
            [
                'name' => $cityName,
            ]
        );
    }
}