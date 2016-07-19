<?php
namespace infrajs\config\search;
use infrajs\cache\Cache;
use infrajs\load\Load;
use infrajs\path\Path;
use infrajs\config\Config;
class Search {
	public static $conf = array(
		"off" => false
	);
	public static function init ()
	{
		//Заполнять Path::$conf['search'] нужно после того как пройдёт инициализация конфигов .infra.json
		//Чтобы значения по умолчанию не заменили сгенерированные значения
		Config::init();
		if (Search::$conf["off"]) return;
		static::checkFS();
		
	}
	public static function checkFS ()
	{
		$search = Cache::exec(array('composer.lock'), __FILE__.'init', function () {
			$search = array();
			$ex = array_merge(array(Path::$conf['cache'], Path::$conf['data']), Path::$conf['search']);
			Search::scan('', function ($src, $level) use (&$search, $ex){
				if (in_array($src, $ex)) return true; //вглубь не идём
				if ($level < 2) return;
				if ($level > 5) return true;
				if (!is_file($src.'.infra.json')) return;
				
				$r = explode('/', $src);
				array_pop($r);
				array_pop($r);
				
				$search[] = implode('/',$r).'/';
				return false; //вглубь не идём и в соседние папки тоже
			});
			return $search;
		});
		Path::$conf['search'] = array_values(array_unique(array_merge(Path::$conf['search'], $search)));

		if (Config::$all) { //Если все конфиги были уже обраны, нужно заного пробежаться по найденным
			for ($i = 0; $i < sizeof($search); $i++) {
				$tsrc = $search[$i];
				if (!is_dir($tsrc)) continue;
				$files = scandir($tsrc);
				foreach ($files as $file) {
					if ($file{0} == '.') continue;
					if (!is_dir($tsrc.$file)) continue;
					Config::load($tsrc.$file.'/.infra.json', $file);
				}
			}
		}
		/*$comp = Load::loadJSON('composer.json');
		if ($comp && !empty($comp['require'])) {	
			foreach ($comp['require'] as $n => $v) {
				$r = explode('/', $n);
				
				if (sizeof($r) != 2) continue;
				$path = 'vendor/'.$r[0].'/';
				if (!in_array($path, Path::$conf['search'])){
					Path::$conf['search'][] = $path;
				}
			}
		}*/
	}
	/**
	 * Рекурсивный скан папки
	 * Функция $fn($src, $level) может возвращать управляющие данные
	 * null - идём дальше и вглубь и в ширь
	 * true - вглубь не идём, в ширь идём - переход к соседней папке
	 * false - вглубь не идём, в ширь не идём - выход на уровень выше
	 **/
	public static function scan($dir, $fn, $level = 0)
	{
		if ($dir === '') {
			$d = opendir('./');	
		} else {
			$dir = Path::theme($dir);
			$d = opendir($dir);
		}
		if ($level) { //Для 0 левел не запускаем $fn
			$r = $fn($dir, $level);
			if ($r === true) return;
			if (!is_null($r)) return $r;
		}
		while ($file = readdir($d)) {
			if ($file{0}=='.') continue;
			$src = $dir.$file;
			if (is_file($src)) continue;
			$src .= '/';
			$r = static::scan($src, $fn, $level+1);
			if ($r === false) {
				$r = null;
				break;
			} 
			if (!is_null($r)) {
				break;
			}
			
		}
		closedir($d);
		return $r;
	}
}
?>