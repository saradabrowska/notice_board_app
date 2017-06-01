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
        if (isset($object['id']) && ctype_digit((string) $object['id'])) {
            // update record
            $id = $object['id'];
            unset($object['id']);

            return $this->db->update('offers', $object, ['id' => $id]);
        } else {
            // add new record
            return $this->db->insert('offers', $object);
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
        $queryBuilder = $this->queryAll($table)
            ->where('offer_types_id = :offer_types_id')
            ->setParameter(':offer_types_id', $match['offer_types_id']);
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
}