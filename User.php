<?php

use \Interfaces\CacheableInterface;
use \Interfaces\BatchInsertInterface;
class User implements CacheableInterface, BatchInsertInterface
{
    protected $db;
    private $table;
    protected $primaryKey = 'id';
    protected $props = [
      'id',
      'name',
      'email',
      'currency',
      'sum'
    ];
    protected $data = [];
    protected static $bindedValues = [];
    protected static $bindCount = 0;
    protected $limit;
    protected $offset;
    protected $conds = [];

    /**
     * User constructor.
     * @param PDO $db
     * @param array $data
     */
    function __construct(PDO $db, $data = [])
    {
        $this->table = 'users';
        $this->db = $db;
        if(!empty($data)){
            $this->fill($data);
        }
    }

    /**
     * @param $data
     */
    public function fill($data){
        foreach ($data as $key => $value){
            $this->{$key} = $value;
        }
    }

    /**
     * @return string
     */
    protected function getSqlFields(){
        return implode(',', $this->props);
    }

    /**
     * @return bool|mixed
     */
    public function save(){
        if($this->getPrimaryKey()){
            $data = $this->getData();
            unset($data[$this->primaryKey]);
            return $this->update($data);
        }
        else {
            $this->insertOne($this->getData());
        }
    }

    /**
     * @param $data
     * @return bool
     */
    public function update($data){
        $sql = "UPDATE {$this->table} SET ";
        foreach ($data as $prop => $value){
            $fields[] = "{$prop}=" . $this->bindValue($value);
        }
        $sqlFields = implode(',', $fields);
        $this->addWhere($this->primaryKey, $this->getPrimaryKey());
        $sql .= "{$sqlFields} {$this->buildWhere()}";
        $stmt = $this->db->prepare($sql);
        if($stmt){
            $result = $stmt->execute($this->getBindedValue());
            $this->reset();
            return $result;
        }
    }

    /**
     * @param $data
     */
    public function insertOne($data){
        $placeholders = [];
        $sql = "INSERT INTO {$this->table} ";
        if(empty($data)){
            $sql .= 'default values';
        }
        else{
            $placeholders[] = $this->bindValue($this->getPrimaryKey());
            foreach ($data as $prop){
                $placeholders[] = $this->bindValue($prop);
            }
            $placeholders = implode(',', $placeholders);
            $sql .= "({$this->getSqlFields()}) VALUES ({$placeholders})";
        }
        $stmt = $this->db->prepare($sql);
        if($stmt){
            $res = $stmt->execute($this->getBindedValue());
            $this->reset();
            if($res){
                $this->setPrimaryKey($this->db->lastInsertId());
            }
        }
    }

    /**
     * @param array $data
     * @return bool|mixed
     */
    public function insertMultiple($data) {
        $sqlValues = [];
        foreach ($data as $k => $userData){
            $this->fill($userData);
            $placeholders[$k][] = $this->bindValue($this->getPrimaryKey());
            foreach ($this->getData() as $prop => $value){
                $placeholders[$k][] = $this->bindValue($value);
            }
        }
        foreach ($placeholders as $placeholderGroup){
            $sqlValues[] = '(' . implode(',', $placeholderGroup) . ')';
        }
        $sqlValuesStr = implode(',', $sqlValues);
        $stmt = $this->db->prepare("INSERT INTO {$this->table} ({$this->getSqlFields()}) VALUES {$sqlValuesStr}");
        if($stmt){
            $result = $stmt->execute($this->getBindedValue());
            $this->reset();
            return $result;
        }
    }

    /**
     * @param $id
     * @return array|mixed
     */
    public function findOne($id) {
        $this->setLimit(1);
        return $this->find([$this->primaryKey => $id]);
    }

    /**
     * @param $conds
     * @return array|mixed
     */
    public function find($conds){
        foreach ($conds as $cond => $param){
            $this->addWhere($cond, $param);
        }
        $sql = "SELECT {$this->getSqlFields()} FROM {$this->table} {$this->buildWhere()} {$this->buildLimitOffset()}";
        $stmt = $this->db->prepare($sql);
        if($stmt){
            $stmt->execute($this->getBindedValue());
            $this->reset();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    /**
     * @param $value
     */
    public function setName($value){
        $value = trim($value);
        if(!isset($value[0])){
            throw new \InvalidArgumentException('Name can\'t be empty or consist from spaces');
        }
        if(isset($value[255])){
            throw new \InvalidArgumentException('Name can\'t have more 255 chars');
        }
        $this->data['name'] = $value;
    }

    /**
     * @param $value
     */
    public function setEmail($value){
        $value = trim($value);
        if(isset($value[255])){
            throw new \InvalidArgumentException('Email can\'t have more 255 chars');
        }
        if(!preg_match('/[A-z]+@[a-z]+\.[a-z]{2,3}/', $value)){
            throw new \InvalidArgumentException('Email is not valid');
        }
        $this->data['email'] = $value;
    }

    /**
     * @param $value
     */
    public function setCurrency($value){
        $value = trim($value);
        if(isset($value[3])){
            throw new \InvalidArgumentException('Currency can\'t have more 3 chars');
        }
        $this->data['currency'] = $value;
    }

    /**
     * @param $value
     */
    public function setSum($value){
        if(!is_numeric($value)){
            throw new \InvalidArgumentException('Sum is not valid');
        }
        $this->data['sum'] = $value;
    }

    /**
     * @param $value
     * @return int
     */
    public function setPrimaryKey($value){
        return $this->data[$this->primaryKey] = (int) $value;
    }

    /**
     * @return mixed|null
     */
    public function getPrimaryKey(){
        return $this->data[$this->primaryKey] ?? null;
    }

    /**
     * @return mixed|null
     */
    public function getName(){
        return $this->data['name'] ?? null;
    }

    /**
     * @return mixed|null
     */
    public function getEmail(){
        return $this->data['email'] ?? null;
    }

    /**
     * @return mixed|null
     */
    public function getCurrency(){
        return $this->data['currency'] ?? null;
    }

    /**
     * @return mixed|null
     */
    public function getSum(){
        return $this->data['sum'] ?? null;
    }

    /**
     * @return array
     */
    public function getData(){
        return $this->data;
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        if (in_array($name, $this->props)) {
            if($name == $this->primaryKey){
                $funcName = 'setPrimaryKey';
            }
            else{
                $funcName = 'set' . ucfirst($name);
            }
            $this->$funcName($value);
        }
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        if (in_array($name, $this->props)) {
            if($name == $this->primaryKey){
                $funcName = 'getPrimaryKey';
            }
            else{
                $funcName = 'get' . ucfirst($name);
            }
            return $this->$funcName();
        }
    }

    /**
     * @return array
     */
    protected function getBindedValue(){
        return self::$bindedValues;
    }

    /**
     * @param $v
     * @return string
     */
    protected function bindValue($v){
        $placeholder = ':' . ++self::$bindCount;
        self::$bindedValues[$placeholder] = $v;
        return $placeholder;
    }

    protected function reset(){
        self::$bindedValues = [];
        self::$bindCount = 0;
        $this->conds = [];
    }

    /**
     * @return string
     */
    protected function buildLimitOffset(){
        return $this->limit ? 'LIMIT ' . ($this->offset ? $this->offset : '') . $this->limit : '';
    }

    /**
     * @return string
     */
    protected function buildWhere(){
        $sql = 'WHERE ';
        foreach ($this->conds as $cond){
           $bindKey =  $this->bindValue($cond['value']);
           $sql .= $cond['column'] . ' ';
           if($cond['statement'] == 'IN'){
               $sql .= 'IN (' . $bindKey . ')';
           } else {
               $sql .= ' = ' . $bindKey;
           }
           $sql .= ' AND ';

        }
        return rtrim($sql,' AND ');
    }

    /**
     * @param $cond
     * @param $param
     * @return $this
     */
    public function addWhere($cond, $param){
        $this->conds[] = [
          'statement' => is_array($param) ? 'IN' : '=',
          'column' => $cond,
          'value' => $param,
        ];
        return $this;
    }

    /**
     * @param $val
     * @return $this
     */
    public function setLimit($val){
        $this->limit = (int) $val;
        return $this;
    }

    /**
     * @param $val
     * @return $this
     */
    public function setOffset($val){
        $this->offset = (int) $val;
        return $this;
    }
}