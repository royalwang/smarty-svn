<?php

/**
* Smarty Internal Plugin CacheResource File
* 
* Implements the file system as resource for the HTML cache
* Version ussing nocache inserts
* 
* @package Smarty
* @subpackage Cacher
* @author Uwe Tews 
*/

/**
* This class does contain all necessary methods for the HTML cache on file system
*/
class Smarty_Internal_CacheResource_File extends Smarty_Internal_PluginBase {
    /**
    * Returns the filepath of the cached template output
    * 
    * @param object $template current template
    * @return string the cache filepath
    */
    public function getCachedFilepath($template)
    {
        return $this->buildCachedFilepath ($template->resource_name, $template->cache_id, $template->compile_id);
    } 

    /**
    * Returns the timpestamp of the cached template output
    * 
    * @param object $template current template
    * @return integer |booelan the template timestamp or false if the file does not exist
    */
    public function getCachedTimestamp($template)
    {
        return ($template->getCachedFilepath() && file_exists($template->getCachedFilepath())) ? filemtime($template->getCachedFilepath()) : false ;
    } 

    /**
    * Returns the cached template output
    * 
    * @param object $template current template
    * @return string |booelan the template content or false if the file does not exist
    */
    public function getCachedContents($template)
    {
        return file_get_contents($template->getCachedFilepath());
    } 

    /**
    * Writes the rendered template output to cache file
    * 
    * @param object $template current template
    * @return boolean status
    */
    public function writeCachedContent($template)
    {
        if (!$template->isEvaluated()) {
            if (!is_object($this->smarty->write_file_object)) {
                $this->smarty->loadPlugin("Smarty_Internal_Write_File");
                $this->smarty->write_file_object = new Smarty_Internal_Write_File;
            } 
            return $this->smarty->write_file_object->writeFile($template->getCachedFilepath(), $template->rendered_content);
        } else {
            return false;
        } 
    } 

    /**
    * Empty cache folder
    * 
    * @param integer $exp_time expiration time
    * @return integer number of cache files deleted
    */
    public function clearAll($exp_time = null)
    {
        return $this->clear(null, null, null, $exp_time);
    } 
    /**
    * Empty cache for a specific template
    * 
    * @param string $resource_name template name
    * @param string $cache_id cache id
    * @param string $compile_id compile id
    * @param integer $exp_time expiration time
    * @return integer number of cache files deleted
    */
    public function clear($resource_name, $cache_id, $compile_id, $exp_time)
    {
        $_dir_sep = $this->smarty->use_sub_dirs ? DIRECTORY_SEPARATOR : '^';
        if (isset($resource_name)) {
            $_resource_part = (string)abs(crc32($resource_name)) . '.' . $resource_name . $this->smarty->php_ext;
        } else {
            $_resource_part = null;
        } 
        $_dir = $this->smarty->cache_dir;
        if (substr($_dir, -1) != DIRECTORY_SEPARATOR) {
            $_dir .= DIRECTORY_SEPARATOR;
        } 
        if ($this->smarty->use_sub_dirs && isset($cache_id)) {
            $_dir .= str_replace('|', $_dir_sep, $cache_id) . $_dir_sep;
        } 
        $_compile_pos = $this->smarty->use_sub_dirs ? 5 : 2;
        $_count = 0;
        $_cacheDirs = new RecursiveDirectoryIterator($_dir);
        $_cache = new RecursiveIteratorIterator($_cacheDirs, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($_cache as $_file) {
            if (strpos($_file, '.svn') !== false) continue;
            if ($_file->isDir()) {
                if (!$_cache->isDot()) {
                    // delete folder if empty
                    @rmdir($_file->getPathname());
                } 
            } else {
                $_parts = explode($_dir_sep, $_file);
                $_parts_count = count($_parts);
                $_parts_compile_pos = $_parts_count - $_compile_pos;
                if ($_parts_compile_pos < 0) {
                    $_parts_compile_pos = 0;
                } 
                if (substr_compare((string)$_file, $_dir, 0, strlen($_dir)) == 0 &&
                        (!isset($resource_name) || $_parts[$_parts_count-1] == $_resource_part) &&
                        (!isset($compile_id) || $_parts[$_parts_compile_pos] == $compile_id)) {
                    if (isset($exp_time)) {
                        if (time() - @filemtime($_file) >= $exp_time) {
                            $_count += unlink((string) $_file) ? 1 : 0;
                        } 
                    } else {
                        $_count += unlink((string) $_file) ? 1 : 0;
                    } 
                } 
            } 
        } 
        return $_count;
    } 
    /**
    * Get system filepath to cached file
    * 
    * @param string $resource_name template name
    * @param string $cache_id cache id
    * @param string $compile_id compile id
    * @return string filepath of cache file
    */
    private function buildCachedFilepath ($resource_name, $cache_id, $compile_id)
    {
//        $_filepath = md5($resource_name); 
        $_filepath = (string)abs(crc32($resource_name));
        // if use_sub_dirs, break file into directories
        if ($this->smarty->use_sub_dirs) {
            $_filepath = substr($_filepath, 0, 2) . DIRECTORY_SEPARATOR
             . substr($_filepath, 2, 2) . DIRECTORY_SEPARATOR
             . substr($_filepath, 4, 2) . DIRECTORY_SEPARATOR
             . $_filepath;
        } 
        $_compile_dir_sep = $this->smarty->use_sub_dirs ? DIRECTORY_SEPARATOR : '^';
        if (isset($cache_id)) {
            $_cache_id = str_replace('|', $_compile_dir_sep, $cache_id) . $_compile_dir_sep;
        } else {
            $_cache_id = '';
        } 
        if (isset($compile_id)) {
            $_compile_id = $compile_id . $_compile_dir_sep;
        } else {
            $_compile_id = '';
        } 
        $_cache_dir = $_template->smarty->cache_dir;
        if (substr($_cache_dir, -1) != DIRECTORY_SEPARATOR) {
            $_cache_dir .= DIRECTORY_SEPARATOR;
        } 

        return $_cache_dir . $_cache_id . $_compile_id . $_filepath . '.' . $resource_name . $this->smarty->php_ext;
    } 
} 

?>
