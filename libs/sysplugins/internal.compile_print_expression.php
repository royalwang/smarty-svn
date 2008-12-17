<?php
/**
* Smarty Internal Plugin Compile Print Expression
* 
* Compiles any tag which will output an expression or variable
* 
* @package Smarty
* @subpackage Compiler
* @author Uwe Tews 
*/
/**
* Smarty Internal Plugin Compile Print Expression Class
*/
class Smarty_Internal_Compile_Print_Expression extends Smarty_Internal_CompileBase {
    /**
    * Compiles code for gererting output from any expression
    * 
    * @param array $args array with attributes from parser
    * @param object $compiler compiler object
    * @return string compiled code
    */
    public function compile($args, $compiler)
    {
        $this->compiler = $compiler; 
        $this->required_attributes = array('value');
        $this->optional_attributes = array('assign','nocache'); 
        // check and get attributes
        $_attr = $this->_get_attributes($args);

        if (isset($_attr['nocache'])) {
            if ($_attr['nocache'] == 'true') {
                $this->compiler->_compiler_status->tag_nocache = true;
            } 
        } 

        if (isset($_attr['assign'])) {
            // assign output to variable
            $output = '<?php $_smarty_tpl->assign(' . $_attr['assign'] . ',' . $_attr['value'] . ');?>';
        } else {
            // display value
            $this->compiler->has_output = true;
            $output = '<?php echo ' . $_attr['value'] . ';?>';
        } 
        return $output;
    } 
} 

?>
