<?php
/**
 * 验证当前workspace是否可以用此插件
 */

require_once (__DIR__ . "/class/GitControl.php");

$gObj = new GitControl();

return $gObj->checkGitActived();
