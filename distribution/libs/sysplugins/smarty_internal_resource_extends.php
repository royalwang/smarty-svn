<?php

/**
 * Smarty Internal Plugin Resource Extends
 * 
 * Implements the file system as resource for Smarty which {extend}s a chain of template files templates
 * 
 * @package Smarty
 * @subpackage TemplateResources
 * @author Uwe Tews 
 * @author Rodney Rehm
 */
class Smarty_Internal_Resource_Extends extends Smarty_Resource {
    
    /**
     * populate Source Object with meta data from Resource
     *
     * @param Smarty_Template_Source $source source object
     * @param Smarty_Internal_Template $_template template object
     * @return void
     */
    public function populate(Smarty_Template_Source $source, Smarty_Internal_Template $_template=null)
    {
        $uid = '';
        $sources = array();
        $components = explode('|', $source->name);
        $exists = true;
        foreach ($components as $component) {
            $s = Smarty_Resource::source(null, $source->smarty, $component);
            $sources[$s->uid] = $s;
            $uid .= $source->filepath;
            if ($_template && $_template->compile_check) {
                $exists == $exists && $s->exists;
            }
        }
        $source->components = $sources;
        $source->filepath = $s->filepath;
        $source->uid = sha1($uid);
        if ($_template && $_template->compile_check) {
            $source->timestamp = $s->timestamp;
            $source->exists = $exists;
        }
        // need the template at getTemplateSource()
        $source->template = $_template;
    }
    
    /**
     * populate Source Object with timestamp and exists from Resource
     *
     * @param Smarty_Template_Source $source source object
     * @return void
     */
    public function populateTimestamp(Smarty_Template_Source $source)
    {
        $source->exists = true;
        foreach ($source->components as $s) {
            $source->exists == $source->exists && $s->exists;
        }
        $source->timestamp = $s->timestamp;
    }
    
    /**
     * Load template's source from files into current template object
     * 
     * @param Smarty_Template_Source $source source object
     * @return string template source
     * @throws SmartyException if source cannot be loaded
     */
    public function getTemplateSource(Smarty_Template_Source $source)
    {
        if (!$source->exists) {
            throw new SmartyException("Unable to read template {$source->type} '{$source->name}'");
        }
        
        $_rdl = preg_quote($source->smarty->right_delimiter);
        $_ldl = preg_quote($source->smarty->left_delimiter);
        $_components = array_reverse($source->components);
        $_first = reset($_components);
        $_last = end($_components);

        foreach ($_components as $_component) {
            // register dependency
            if ($_component != $_first) {
                $source->template->properties['file_dependency'][$_component->uid] = array($_component->filepath, $_component->timestamp, $_component->type);
            } 
            
            // read content
            $source->filepath = $_component->filepath;
            $_content = $_component->content;
            
            // extend sources
            if ($_component != $_last) {
                if (preg_match_all("!({$_ldl}block\s(.+?){$_rdl})!", $_content, $_open) !=
                        preg_match_all("!({$_ldl}/block{$_rdl})!", $_content, $_close)) {
                    $source->smarty->triggerError("unmatched {block} {/block} pairs in template {$_component->type} '{$_component->name}'");
                } 
                preg_match_all("!{$_ldl}block\s(.+?){$_rdl}|{$_ldl}/block{$_rdl}!", $_content, $_result, PREG_OFFSET_CAPTURE);
                $_result_count = count($_result[0]);
                $_start = 0;
                while ($_start < $_result_count) {
                    $_end = 0;
                    $_level = 1;
                    while ($_level != 0) {
                        $_end++;
                        if (!strpos($_result[0][$_start + $_end][0], '/')) {
                            $_level++;
                        } else {
                            $_level--;
                        } 
                    } 
                    $_block_content = str_replace($source->smarty->left_delimiter . '$smarty.block.parent' . $source->smarty->right_delimiter, '%%%%SMARTY_PARENT%%%%',
                        substr($_content, $_result[0][$_start][1] + strlen($_result[0][$_start][0]), $_result[0][$_start + $_end][1] - $_result[0][$_start][1] - + strlen($_result[0][$_start][0])));
                    Smarty_Internal_Compile_Block::saveBlockData($_block_content, $_result[0][$_start][0], $source->template, $_component->filepath);
                    $_start = $_start + $_end + 1;
                } 
            } else {
                return $_content;
            } 
        } 
    }

    /**
     * Determine basename for compiled filename
     *
     * @param Smarty_Template_Source $source source object
     * @return string resource's basename
     */
    public function getBasename(Smarty_Template_Source $source)
    {
        return basename($source->filepath);
    }
} 

?>