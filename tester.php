<?php
use infrajs\path\Path;
use infrajs\ans\Ans;

use infrajs\config\search\Search;

if(!is_file('vendor/autoload.php')) {
	chdir('../../../');
	require_once('vendor/autoload.php');
}
//\infrajs\config\Config::init();
Search::init();

$ans = array();
$ans['search'] = Path::$conf['search'];

if (sizeof(Path::$conf['search']) == 1) {
	//$ans['class']='bg-warning';
	return Ans::err($ans,'Только одна папка поиска - '.print_r(Path::$conf['search'], true).', такое может быть ещё для пустого проекта. Как минимум должен быть найден vendor/ded c domready');
}
return Ans::ret($ans);

?>