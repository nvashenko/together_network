<?php


namespace Interfaces;


interface BatchInsertInterface
{
    /**
     * @param array $data
     * @return mixed
     */
    public function insertMultiple(array $data);

}