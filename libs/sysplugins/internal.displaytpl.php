<?php

/**
* Smarty plugin
* 
* @package Smarty
* @subpackage plugins
*/

class Smarty_Internal_DisplayTPL extends Smarty_Internal_DisplayBase {
    public function display($tpl, $tpl_vars)
    { 
        // get compiled filename/filepath
        $_compiled_filename = $this->_get_compiled_filename($tpl_filename);
        $_compiled_filepath = $this->smarty->compile_dir . $_compiled_filename;
        $_tpl_filepath = $this->smarty->template_dir . $tpl; 

        if (!file_exists($_tpl_filepath)) {
                $this->smarty->trigger_fatal_error("Template file ".$_tpl_filepath." does not exist");
         }
        // compile if needed
        if (!file_exists($_compiled_filepath) || filemtime($_compiled_filepath) !== filemtime($_tpl_filepath) || $this->smarty->force_compile
                ) {
            $this->_compiler = new Smarty_Internal_Compiler; 
            // read template file
            $_content = file_get_contents($_tpl_filepath);

            $this->_compiler->compile($_content, $_tpl_filepath, $_compiled_filepath);

            if ($this->smarty->compile_error) {
                // Display error and die
                $this->smarty->trigger_fatal_error("Template compilation error");
            } 
            // make tpl and compiled file timestamp match
            touch($_compiled_filepath, filemtime($_tpl_filepath));
        } 

        extract($tpl_vars);
        include($_compiled_filepath);
    } 

    private function _get_compiled_filename($tpl)
    {
        return md5($tpl) . $this->smarty->php_ext;
    } 
} 

?>