<?php

interface Control
{
    /**
     * 设置Framework到class
     * @param $ci
     * @return mixed
     */
    public function setCI(& $ci);

    /**
     * 建立与workspace的连接
     * @return mixed
     */
    public function connect();

    /**
     * 获取文件内容
     * @param $filename
     * @return mixed
     */
    public function getFileContent($filename);

    /**
     * 获取目录内文件列表，如果是目录，则包括该目录是否有文件的标示
     * @param $path
     * @return mixed
     */
    public function getFolderLists($path);

    /**
     * 保存文件
     * @param $from
     * @param $to
     * @return mixed
     */
    public function saveFile($from, $to);


    /**
     * 重命名一个文件或者目录
     * @param $file
     * @param $newfile_name
     * @return mixed
     */
    public function renameFile($file, $newfile_name);

    /**
     * 删除一个文件或者目录
     * @param $file
     * @return mixed
     */
    public function deleteFile($file);

    /**
     * 添加一个空文件
     * @param $file
     * @return mixed
     */
    public function addFile($file);

    /**
     * 添加文件夹
     * @param $file
     * @return mixed
     */
    public function addFolder($file);

    /**
     * 文件或目录是否存在
     * @param $file
     * @return mixed
     */
    public function fileExists($file);
}