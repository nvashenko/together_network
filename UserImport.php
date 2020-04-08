<?php

use Interfaces\BatchInsertInterface;

class UserImport  implements SplObserver
{
    protected $batchSize;
    protected $data;
    protected $batchHandler;
    public function __construct(BatchInsertInterface $batchHandler, $batchSize = 100)
    {
        $this->batchSize = $batchSize;
        $this->batchHandler = $batchHandler;
    }

    public function import($data){
        $this->batchHandler->insertMultiple($data);
    }

    public function update(SplSubject $subject, $event = '', $data = [])
    {
        if($event == 'new_line' && isset($data)){
            $this->data[] = $data;
            if(count($this->data) == $this->batchSize){
                $this->import($this->data);
                $this->data = [];
            }
        }
        if($event == 'dataend' && count($this->data)){
            $this->import($this->data);
        }
    }

}