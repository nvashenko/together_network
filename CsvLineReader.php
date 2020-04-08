<?php

class CsvLineReader implements SplSubject
{

    protected $listeners;

    protected $file;
    protected $delim;
    protected $enclosure;
    protected $escape;
    protected $offset;
    protected $header = [];
    private $lineData = [];

    public function __construct(SplFileObject $file, $delim = ',', $enclosure = '"', $escape = '\\' )
    {
        $this->delim = $delim;
        $this->enclosure = $enclosure;
        $this->escape = $escape;
        $this->file = $file;
    }

    public function setOffset($offset){
        $this->offset =  $offset;
    }

    public function getOffset(){
        return $this->offset;
    }

    public function readHeader($offset = 0){
        if($offset != 0) {
            $this->file->fseek($offset);
        }
        $header = $this->file->fgetcsv($this->delim, $this->enclosure, $this->escape);
        $this->setHeader($header);
    }

    public function setHeader($header) {
        $this->header = $header;
    }

    public function getHeader() {
        return $this->header;
    }

    public function attach($listener, $event = '')
    {
        $this->listeners[$event][] = $listener;
    }

    public function detach(SplObserver $listener, $event = '')
    {
        foreach ($this->listeners[$event] as $key => $obj) {
            if ($listener === $obj) {
                unset($this->listeners[$key]);
            }
        }
    }

    public function notify($event = '')
    {
        foreach ($this->listeners[$event] as $key => $listener) {
            $listener->update($this, $event, $this->lineData);
        }
    }

    public function read(){
        while (!$this->file->eof()){
            $this->lineData = [];
            $data = $this->file->fgetcsv($this->delim, $this->enclosure, $this->escape);
            if($header = $this->getHeader()){
                $sizeDiff = count($header) - count($data);
                if($sizeDiff > 0){
                    $data = array_pad($data, $sizeDiff, null);
                }
                $this->lineData = array_combine($header, $data);
            }
            else{
                $this->lineData = $data;
            }
            $this->notify('new_line');
        }
        $this->notify('dataend');
    }

}