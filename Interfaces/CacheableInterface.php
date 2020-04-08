<?php

namespace Interfaces;

interface CacheableInterface
{
    /**
     * @param $conds
     * @return mixed
     */
    public function find($conds);

    /**
     * @param $id
     * @return mixed
     */
    public function findOne($id);

    /**
     * @return mixed
     */
    public function save();

    /**
     * @return mixed
     */
    public function getPrimaryKey();

}