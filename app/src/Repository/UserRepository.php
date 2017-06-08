<?php
/**
 * User repository
 */

namespace Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineDbalAdapter;

/**
 * Class UserRepository.
 *
 * @package Repository
 */
class UserRepository
{
    /**
     * Doctrine DBAL connection.
     *
     * @var \Doctrine\DBAL\Connection $db
     */
    protected $db;
    /**
     * Number of items per page.
     *
     * const int NUM_ITEMS
     */
    const NUM_ITEMS = 2;

    /**
     * TagRepository constructor.
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

    public function findAll()
    {
        $queryBuilder = $this->queryAll();
        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * Find one record.
     *
     * @param string $id Element id
     *
     * @return array|mixed Result
     */
    public function findOneById($id)
    {
        $queryBuilder = $this->queryAll()
            ->where('login = :id')
            ->setParameter(':id', $id);
        $result = $queryBuilder->execute()->fetch();

        if ($result) {
            $user_data = $this->findUserData($result['id']);
            $result = array_merge($result, $user_data);
        }
        return !$result ? [] : $result;
    }


    /**
     * Save record.
     *
     * @param array $bookmark Bookmark
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function save($user)
    {
        $this->db->beginTransaction();

        try {
            $email = $user['email'];
            unset($user['email']);
            unset($user['password_confirmation']);
            $user['password'] = password_hash($user['password'], PASSWORD_BCRYPT);
            $this->db->insert('users', $user);
            $userId = $this->db->lastInsertId();
            $this->addEmail($userId, $email);

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

    /**
     * Query all records.
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder Result
     */
    protected function queryAll()
    {
        $queryBuilder = $this->db->createQueryBuilder();
        return $queryBuilder->select('*')
            ->from('users');
    }


    /**
     * Loads user by login.
     *
     * @param string $login User login
     * @throws UsernameNotFoundException
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return array Result
     */
    public function loadUserByLogin($login)
    {
        try {
            $user = $this->getUserByLogin($login);

            if (!$user || !count($user)) {
                throw new UsernameNotFoundException(
                    sprintf('Username "%s" does not exist.', $login)
                );
            }

            $roles = $this->getUserRoles($user['id']);

            if (!$roles || !count($roles)) {
                throw new UsernameNotFoundException(
                    sprintf('Username "%s" does not exist.', $login)
                );
            }

            return [
                'login' => $user['login'],
                'password' => $user['password'],
                'roles' => $roles,
            ];
        } catch (DBALException $exception) {
            throw new UsernameNotFoundException(
                sprintf('Username "%s" does not exist.', $login)
            );
        } catch (UsernameNotFoundException $exception) {
            throw $exception;
        }
    }

    /**
     * Gets user data by login.
     *
     * @param string $login User login
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return array Result
     */
    public function getUserByLogin($login)
    {
        try {
            $queryBuilder = $this->db->createQueryBuilder();
            $queryBuilder->select('u.id', 'u.login', 'u.password')
                ->from('users', 'u')
                ->where('u.login = :login')
                ->setParameter(':login', $login, \PDO::PARAM_STR);

            return $queryBuilder->execute()->fetch();
        } catch (DBALException $exception) {
            return [];
        }
    }

    /**
     * Gets user roles by User ID.
     *
     * @param integer $userId User ID
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return array Result
     */
    public function getUserRoles($userId)
    {
        $roles = [];

        try {
            $queryBuilder = $this->db->createQueryBuilder();
            $queryBuilder->select('r.name')
                ->from('users', 'u')
                ->innerJoin('u', 'roles', 'r', 'u.roles_id = r.id')
                ->where('u.id = :id')
                ->setParameter(':id', $userId, \PDO::PARAM_INT);
            $result = $queryBuilder->execute()->fetchAll();

            if ($result) {
                $roles = array_column($result, 'name');
            }

            return $roles;
        } catch (DBALException $exception) {
            return $roles;
        }
    }

    /**
     * Add linked tags.
     *
     * @param int $bookmarkId Bookmark Id
     * @param array $tagsIds Tags Ids
     */
    protected function addEmail($userId, $email)
    {
        $this->db->insert(
            'users_data',
            [
                'users_id' => $userId,
                'email' => $email,
            ]
        );
    }

    /**
     * Finds linked tags Ids.
     *
     * @param int $bookmarkId Bookmark Id
     *
     * @return array Result
     */
    protected function findUserData($userId)
    {
        $queryBuilder = $this->db->createQueryBuilder()
            ->select('email', 'phone_number', 'first_name', 'last_name')
            ->from('users_data')
            ->where('users_id = :user_id')
            ->setParameter(':user_id', $userId, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetchAll();
        return !empty($result) ? current($result) : [];
    }
}
