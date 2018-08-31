<?php

interface Control
{
    public function connect();
    public function getFileContent($filename);
    public function getFolderLists($path);
    public function saveFile($from, $to);
    public function setCI(& $ci);
}