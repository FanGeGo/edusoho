<?php
namespace Topxia\Service\MoneyCard\Dao\Impl;

use Topxia\Service\Common\BaseDao;

class MoneyCardDaoImpl extends BaseDao
{
	protected $table = 'money_card';

	public function getMoneyCard($id)
    {
		$sql = "SELECT * FROM {$this->table} WHERE id = ? LIMIT 1";

        return $this->getConnection()->fetchAssoc($sql, array($id)) ? : null;
	}

	public function searchMoneyCards($conditions, $orderBy, $start, $limit)
    {
        $orderBy = $this->testOrderBy($orderBy, array('id','createdTime'));

        $this->filterStartLimit($start, $limit);
        $builder = $this->createMoneyCardQueryBuilder($conditions)
            ->select('*')
            ->orderBy($orderBy[0], $orderBy[1])
            ->setFirstResult($start)
            ->setMaxResults($limit);

        return $builder->execute()->fetchAll() ? : array();
    }

    public function searchMoneyCardsCount($conditions)
    {
        $builder = $this->createMoneyCardQueryBuilder($conditions)
            ->select('COUNT(id)');

        return $builder->execute()->fetchColumn(0);
    }

    public function addMoneyCard ($moneyCards, $number)
    {
        if(empty($moneyCards)){ return array(); }

        $sql = "INSERT INTO $this->table (cardId, password, validTime, rechargeStatus, batchId)     VALUE ";
        for ($i=0; $i < $number; $i++) {
            $sql .= "(?, ?, ?, ?, ?),";
        }

        $sql = substr($sql, 0, -1);

        return $this->getConnection()->executeUpdate($sql, $moneyCards);
    }

    public function isCardIdAvaliable ($moneyCardIds)
    {
        $str = implode(',', array_map(function($value){ return "'".$value."'"; },
            array_keys($moneyCardIds)));

        $sql = "select COUNT(id) from ".$this->table." where cardId in (".$str.")";

        $result = $this->getConnection()->fetchAll($sql);

        return $result[0]["COUNT(id)"] == 0 ? true : false;
    }

    public function updateMoneyCard ($id, $fields)
    {
        $this->getConnection()->update($this->table, $fields, array('id' => $id));
        return $this->getMoneyCard($id);
    }

    public function deleteMoneyCard ($id)
    {
        $this->getConnection()->delete($this->table, array('id' => $id));
    }

    public function updateBatch ($identifier, $fields)
    {
        $this->getConnection()->update($this->table, $fields, $identifier);
    }

    public function deleteBatch ($fields)
    {
        $sql = "DELETE FROM ".$this->table." WHERE batchId = ? AND rechargeStatus != ?";
        $this->getConnection()->executeUpdate($sql, $fields);
    }

    private function createMoneyCardQueryBuilder($conditions)
    {
        return $this->createDynamicQueryBuilder($conditions)
            ->from($this->table, 'money_card')
            ->andWhere('promoted = :promoted')
            ->andWhere('cardId = :cardId')
            ->andWhere('validTime = :validTime')
            ->andWhere('batchId = :batchId');
    }

    private function testOrderBy (array $orderBy, array $allowedOrderByFields)
    {
        if (count($orderBy) != 2) {
            throw new Exception("参数错误", 1);
        }

        $orderBy = array_values($orderBy);
        if (!in_array($orderBy[0], $allowedOrderByFields)){
            throw new Exception("参数错误", 1);
        }
        if (!in_array($orderBy[1], array('ASC','DESC'))){
            throw new Exception("参数错误", 1);
        }

        return $orderBy;
    }
}