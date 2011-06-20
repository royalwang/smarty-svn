<?php
/**
 * Project:     Smarty: the PHP compiling template engine
 * File:        smarty_internal_utility.php
 * SVN:         $Id: $
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * For questions, help, comments, discussion, etc., please join the
 * Smarty mailing list. Send a blank e-mail to
 * smarty-discussion-subscribe@googlegroups.com
 *
 * @link http://www.smarty.net/
 * @copyright 2008 New Digital Group, Inc.
 * @author Monte Ohrt <monte at ohrt dot com>
 * @author Uwe Tews
 * @package Smarty
 * @subpackage PluginsInternal
 * @version 3-SVN$Rev: 3286 $
 */


/**
 * Utility class
 *
 * @package Smarty
 * @subpackage Security
 */
class Smarty_Internal_Utility {

    /**
     * private constructor to prevent calls creation of new instances
     */
    private final function __construct()
    {
        // intentionally left blank
    }

    /**
     * Compile all template files
     *
     * @param string $extension     file extension
     * @param bool   $force_compile force all to recompile
     * @param int    $time_limit
     * @param int    $max_errors
     * @param Smarty $smarty
     * @return integer number of template files recompiled
     * @todo  Missing parameter documentation
     * @todo  The parameter list was invalid. Check if this change is correct.
     */
    public static function compileAllTemplates($extention, $force_compile, $time_limit, $max_errors, $smarty)
    {
        // switch off time limit
        if (function_exists('set_time_limit')) {
            @set_time_limit($time_limit);
        }
        $smarty->force_compile = $force_compile;
        $_count = 0;
        $_error_count = 0;
        // loop over array of template directories
        foreach($smarty->getTemplateDir() as $_dir) {
            $_compileDirs = new RecursiveDirectoryIterator($_dir);
            $_compile = new RecursiveIteratorIterator($_compileDirs);
            foreach ($_compile as $_fileinfo) {
                if (substr($_fileinfo->getBasename(),0,1) == '.' || strpos($_fileinfo, '.svn') !== false) continue;
                $_file = $_fileinfo->getFilename();
                if (!substr_compare($_file, $extention, - strlen($extention)) == 0) continue;
                if ($_fileinfo->getPath() == substr($_dir, 0, -1)) {
                   $_template_file = $_file;
                } else {
                   $_template_file = substr($_fileinfo->getPath(), strlen($_dir)) . DS . $_file;
                }
                echo '<br>', $_dir, '---', $_template_file;
                flush();
                $_start_time = microtime(true);
                try {
                    $_tpl = $smarty->createTemplate($_template_file,null,null,null,false);
                    if ($_tpl->mustCompile()) {
                        $_tpl->compileTemplateSource();
                        echo ' compiled in  ', microtime(true) - $_start_time, ' seconds';
                        flush();
                    } else {
                        echo ' is up to date';
                        flush();
                    }
                }
                catch (Exception $e) {
                    echo 'Error: ', $e->getMessage(), "<br><br>";
                    $_error_count++;
                }
                // free memory
                $smarty->template_objects = array();
                $_tpl->smarty->template_objects = array();
                $_tpl = null;
                if ($max_errors !== null && $_error_count == $max_errors) {
                    echo '<br><br>too many errors';
                    exit();
                }
            }
        }
        return $_count;
    }

    /**
     * Compile all config files
     *
     * @param string $extension     file extension
     * @param bool   $force_compile force all to recompile
     * @param int    $time_limit
     * @param int    $max_errors
     * @param Smarty $smarty
     * @return integer number of template files recompiled
     * @todo  Missing parameter documentation
     * @todo  The parameter list was invalid. Check if this change is correct.
     */
    public static function compileAllConfig($extention, $force_compile, $time_limit, $max_errors, $smarty)
    {
        // switch off time limit
        if (function_exists('set_time_limit')) {
            @set_time_limit($time_limit);
        }
        $smarty->force_compile = $force_compile;
        $_count = 0;
        $_error_count = 0;
        // loop over array of template directories
        foreach($smarty->getConfigDir() as $_dir) {
            $_compileDirs = new RecursiveDirectoryIterator($_dir);
            $_compile = new RecursiveIteratorIterator($_compileDirs);
            foreach ($_compile as $_fileinfo) {
                if (substr($_fileinfo->getBasename(),0,1) == '.' || strpos($_fileinfo, '.svn') !== false) continue;
                $_file = $_fileinfo->getFilename();
                if (!substr_compare($_file, $extention, - strlen($extention)) == 0) continue;
                if ($_fileinfo->getPath() == substr($_dir, 0, -1)) {
                    $_config_file = $_file;
                } else {
                    $_config_file = substr($_fileinfo->getPath(), strlen($_dir)) . DS . $_file;
                }
                echo '<br>', $_dir, '---', $_config_file;
                flush();
                $_start_time = microtime(true);
                try {
                    $_config = new Smarty_Internal_Config($_config_file, $smarty);
                    if ($_config->mustCompile()) {
                        $_config->compileConfigSource();
                        echo ' compiled in  ', microtime(true) - $_start_time, ' seconds';
                        flush();
                    } else {
                        echo ' is up to date';
                        flush();
                    }
                }
                catch (Exception $e) {
                    echo 'Error: ', $e->getMessage(), "<br><br>";
                    $_error_count++;
                }
                if ($max_errors !== null && $_error_count == $max_errors) {
                    echo '<br><br>too many errors';
                    exit();
                }
            }
        }
        return $_count;
    }

    /**
     * Delete compiled template file
     *
     * @param string  $resource_name template name
     * @param string  $compile_id    compile id
     * @param integer $exp_time      expiration time
     * @param Smarty  $smarty        Smarty instance
     * @return integer number of template files deleted
     * @todo  The parameter list was invalid. Check if this change is correct.
     */
    public static function clearCompiledTemplate($resource_name, $compile_id, $exp_time, $smarty)
    {
        $_compile_dir = $smarty->getCompileDir();
        $_compile_id = isset($compile_id) ? preg_replace('![^\w\|]+!', '_', $compile_id) : null;
        $_dir_sep = $smarty->use_sub_dirs ? DS : '^';
        if (isset($resource_name)) {
            $_resource_part_1 = $resource_name . '.php';
            $_resource_part_2 = $resource_name . '.cache' . '.php';
        } else {
            $_resource_part = '';
        }
        $_dir = $_compile_dir;
        if ($smarty->use_sub_dirs && isset($_compile_id)) {
            $_dir .= $_compile_id . $_dir_sep;
        }
        if (isset($_compile_id)) {
            $_compile_id_part = $_compile_dir . $_compile_id . $_dir_sep;
        }
        $_count = 0;
        $_compileDirs = new RecursiveDirectoryIterator($_dir);
        $_compile = new RecursiveIteratorIterator($_compileDirs, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($_compile as $_file) {
            if (substr($_file->getBasename(), 0, 1) == '.' || strpos($_file, '.svn') !== false)
                continue;
            if ($_file->isDir()) {
                if (!$_compile->isDot()) {
                    // delete folder if empty
                    @rmdir($_file->getPathname());
                }
            } else {
                if ((!isset($_compile_id) || (strlen((string) $_file) > strlen($_compile_id_part) && substr_compare((string) $_file, $_compile_id_part, 0, strlen($_compile_id_part)) == 0)) &&
                        (!isset($resource_name) || (strlen((string) $_file) > strlen($_resource_part_1) && substr_compare((string) $_file, $_resource_part_1, - strlen($_resource_part_1), strlen($_resource_part_1)) == 0) ||
                        (strlen((string) $_file) > strlen($_resource_part_2) && substr_compare((string) $_file, $_resource_part_2, - strlen($_resource_part_2), strlen($_resource_part_2)) == 0))) {
                    if (isset($exp_time)) {
                        if (time() - @filemtime($_file) >= $exp_time) {
                            $_count += @ unlink((string) $_file) ? 1 : 0;
                        }
                    } else {
                        $_count += @ unlink((string) $_file) ? 1 : 0;
                    }
                }
            }
        }
        return $_count;
    }

    /**
     * Return array of tag/attributes of all tags used by an template
     *
     * @param Smarty_Internal_Template $templae template object
     * @return array of tag/attributes
     */
    public static function getTags(Smarty_Internal_Template $template)
    {
        $template->smarty->get_used_tags = true;
        $template->compileTemplateSource();
        return $template->used_tags;
    }

    
    /**
     * diagnose Smarty setup
     *
     * If $errors is secified, the diagnostic report will be appended to the array, rather than being output.
     * @param Smarty $smarty Smarty instance to test
     * @param array $results array to push results into rather than outputting them
     * @return bool status, true if everything is fine, false else
     */
    public static function testInstall($smarty, &$errors=null)
    {
        $status = true;
        
        if ($errors === null) {
            echo "<PRE>\n";
            echo "Smarty Installation test...\n";
            echo "Testing template directory...\n";
        }
        
        // test if all registered template_dir are accessible
        foreach($smarty->getTemplateDir() as $template_dir) {
            if (!is_dir($template_dir)) {
                $status = false;
                $message = "FAILED: $template_dir is not a directory";
                if ($errors === null) {
                    echo $message . ".\n";
                } else {
                    $errors['template_dir'] = $message;
                }
            } elseif (!is_readable($template_dir)) {
                $status = false;
                $message = "FAILED: $template_dir is not readable";
                if ($errors === null) {
                    echo $message . ".\n";
                } else {
                    $errors['template_dir'] = $message;
                }
            } else {
                if ($errors === null) {
                    echo "$template_dir is OK.\n";
                }
            }
        }
        
        
        if ($errors === null) {
            echo "Testing compile directory...\n";
        }
        
        // test if registered compile_dir is accessible
        $_compile_dir = $smarty->getCompileDir();
        if (!is_dir($_compile_dir)) {
            $status = false;
            $message = "FAILED: {$_compile_dir} is not a directory";
            if ($errors === null) {
                echo $message . ".\n";
            } else {
                $errors['compile_dir'] = $message;
            }
        } elseif (!is_readable($_compile_dir)) {
            $status = false;
            $message = "FAILED: {$_compile_dir} is not readable";
            if ($errors === null) {
                echo $message . ".\n";
            } else {
                $errors['compile_dir'] = $message;
            }
        } elseif (!is_writable($_compile_dir)) {
            $status = false;
            $message = "FAILED: {$_compile_dir} is not writable";
            if ($errors === null) {
                echo $message . ".\n";
            } else {
                $errors['compile_dir'] = $message;
            }
        } else {
            if ($errors === null) {
                echo "{$_compile_dir} is OK.\n";
            }
        }
        
        
        if ($errors === null) {
            echo "Testing plugins directory...\n";
        }
        
        // test if all registered plugins_dir are accessible
        // and if core plugins directory is still registered
        $_core_plugins_dir = realpath(dirname(__FILE__) .'/../plugins');
        $_core_plugins_available = false;
        foreach($smarty->getPluginsDir() as $plugin_dir) {
            if (!is_dir($plugin_dir)) {
                $status = false;
                $message = "FAILED: $plugin_dir is not a directory";
                if ($errors === null) {
                    echo $message . ".\n";
                } else {
                    $errors['plugins_dir'] = $message;
                }
            } elseif (!is_readable($plugin_dir)) {
                $status = false;
                $message = "FAILED: $plugin_dir is not readable";
                if ($errors === null) {
                    echo $message . ".\n";
                } else {
                    $errors['plugins_dir'] = $message;
                }
            } elseif ($_core_plugins_dir && $_core_plugins_dir == realpath($plugin_dir)) {
                $_core_plugins_available = true;
            } else {
                if ($errors === null) {
                    echo "$plugin_dir is OK.\n";
                }
            }
        }
        if (!$_core_plugins_available) {
            $status = false;
            $message = "WARNING: Smarty's own libs/plugins is not available";
            if ($errors === null) {
                echo $message . ".\n";
            } elseif (!isset($errors['plugins_dir'])) {
                $errors['plugins_dir'] = $message;
            }
        }
        
        if ($errors === null) {
            echo "Testing cache directory...\n";
        }

        $_cache_dir = $smarty->getCacheDir();

        // test if all registered cache_dir is accessible
        if (!is_dir($_cache_dir)) {
            $status = false;
            $message = "FAILED: {$_cache_dir} is not a directory";
            if ($errors === null) {
                echo $message . ".\n";
            } else {
                $errors['cache_dir'] = $message;
            }
        } elseif (!is_readable($_cache_dir)) {
            $status = false;
            $message = "FAILED: {$_cache_dir} is not readable";
            if ($errors === null) {
                echo $message . ".\n";
            } else {
                $errors['cache_dir'] = $message;
            }
        } elseif (!is_writable($_cache_dir)) {
            $status = false;
            $message = "FAILED: {$_cache_dir} is not writable";
            if ($errors === null) {
                echo $message . ".\n";
            } else {
                $errors['cache_dir'] = $message;
            }
        } else {
            if ($errors === null) {
                echo "{$_cache_dir} is OK.\n";
            }
        }
        
        
        if ($errors === null) {
            echo "Testing configs directory...\n";
        }

        // test if all registered config_dir are accessible
        foreach($smarty->getConfigDir() as $config_dir) {
            if (!is_dir($config_dir)) {
                $status = false;
                $message = "FAILED: $config_dir is not a directory";
                if ($errors === null) {
                    echo $message . ".\n";
                } else {
                    $errors['config_dir'] = $message;
                }
            } elseif (!is_readable($config_dir)) {
                $status = false;
                $message = "FAILED: $config_dir is not readable";
                if ($errors === null) {
                    echo $message . ".\n";
                } else {
                    $errors['config_dir'] = $message;
                }
            } else {
                if ($errors === null) {
                    echo "$config_dir is OK.\n";
                }
            }
        }
        
        
        if ($errors === null) {
            echo "Testing sysplugin files...\n";
        }
        // test if sysplugins are available
        $source = SMARTY_SYSPLUGINS_DIR;
        if (is_dir($source)) {
            $expected = array(
                "smarty_cacheresource.php" => true,
                "smarty_cacheresource_custom.php" => true,
                "smarty_cacheresource_keyvaluestore.php" => true,
                "smarty_config_source.php" => true,
                "smarty_internal_cacheresource_file.php" => true,
                "smarty_internal_compile_append.php" => true,
                "smarty_internal_compile_assign.php" => true,
                "smarty_internal_compile_block.php" => true,
                "smarty_internal_compile_break.php" => true,
                "smarty_internal_compile_call.php" => true,
                "smarty_internal_compile_capture.php" => true,
                "smarty_internal_compile_config_load.php" => true,
                "smarty_internal_compile_continue.php" => true,
                "smarty_internal_compile_debug.php" => true,
                "smarty_internal_compile_eval.php" => true,
                "smarty_internal_compile_extends.php" => true,
                "smarty_internal_compile_for.php" => true,
                "smarty_internal_compile_foreach.php" => true,
                "smarty_internal_compile_function.php" => true,
                "smarty_internal_compile_if.php" => true,
                "smarty_internal_compile_include.php" => true,
                "smarty_internal_compile_include_php.php" => true,
                "smarty_internal_compile_insert.php" => true,
                "smarty_internal_compile_ldelim.php" => true,
                "smarty_internal_compile_nocache.php" => true,
                "smarty_internal_compile_private_block_plugin.php" => true,
                "smarty_internal_compile_private_function_plugin.php" => true,
                "smarty_internal_compile_private_modifier.php" => true,
                "smarty_internal_compile_private_object_block_function.php" => true,
                "smarty_internal_compile_private_object_function.php" => true,
                "smarty_internal_compile_private_print_expression.php" => true,
                "smarty_internal_compile_private_registered_block.php" => true,
                "smarty_internal_compile_private_registered_function.php" => true,
                "smarty_internal_compile_private_special_variable.php" => true,
                "smarty_internal_compile_rdelim.php" => true,
                "smarty_internal_compile_section.php" => true,
                "smarty_internal_compile_setfilter.php" => true,
                "smarty_internal_compile_while.php" => true,
                "smarty_internal_compilebase.php" => true,
                "smarty_internal_config.php" => true,
                "smarty_internal_config_file_compiler.php" => true,
                "smarty_internal_configfilelexer.php" => true,
                "smarty_internal_configfileparser.php" => true,
                "smarty_internal_data.php" => true,
                "smarty_internal_debug.php" => true,
                "smarty_internal_filter_handler.php" => true,
                "smarty_internal_function_call_handler.php" => true,
                "smarty_internal_get_include_path.php" => true,
                "smarty_internal_nocache_insert.php" => true,
                "smarty_internal_parsetree.php" => true,
                "smarty_internal_resource_eval.php" => true,
                "smarty_internal_resource_extends.php" => true,
                "smarty_internal_resource_file.php" => true,
                "smarty_internal_resource_registered.php" => true,
                "smarty_internal_resource_stream.php" => true,
                "smarty_internal_resource_string.php" => true,
                "smarty_internal_smartytemplatecompiler.php" => true,
                "smarty_internal_template.php" => true,
                "smarty_internal_templatebase.php" => true,
                "smarty_internal_templatecompilerbase.php" => true,
                "smarty_internal_templatelexer.php" => true,
                "smarty_internal_templateparser.php" => true,
                "smarty_internal_utility.php" => true,
                "smarty_internal_write_file.php" => true,
                "smarty_resource.php" => true,
                "smarty_resource_custom.php" => true,
                "smarty_resource_recompiled.php" => true,
                "smarty_resource_uncompiled.php" => true,
                "smarty_security.php" => true,
            );
            $iterator = new DirectoryIterator($source);
            foreach ($iterator as $file) {
                if (!$file->isDot()) {
                    $filename = $file->getFilename();
                    if (isset($expected[$filename])) {
                        unset($expected[$filename]);
                    }
                }
            }
            if ($expected) {
                $status = false;
                $message = "FAILED: files missing from libs/sysplugins: ". join(', ', array_keys($expected));
                if ($errors === null) {
                    echo $message . ".\n";
                } else {
                    $errors['sysplugins'] = $message;
                }
            } elseif ($errors === null) {
                echo "... OK\n";
            }
        } else {
            $status = false;
            $message = "FAILED: ". SMARTY_SYSPLUGINS_DIR .' is not a directory';
            if ($errors === null) {
                echo $message . ".\n";
            } else {
                $errors['sysplugins_dir_constant'] = $message;
            }
        }

        if ($errors === null) {
            echo "Testing plugin files...\n";
        }
        // test if core plugins are available
        $source = SMARTY_PLUGINS_DIR;
        if (is_dir($source)) {
            $expected = array(
                "block.textformat.php" => true,
                "function.counter.php" => true,
                "function.cycle.php" => true,
                "function.fetch.php" => true,
                "function.html_checkboxes.php" => true,
                "function.html_image.php" => true,
                "function.html_options.php" => true,
                "function.html_radios.php" => true,
                "function.html_select_date.php" => true,
                "function.html_select_time.php" => true,
                "function.html_table.php" => true,
                "function.mailto.php" => true,
                "function.math.php" => true,
                "modifier.capitalize.php" => true,
                "modifier.date_format.php" => true,
                "modifier.debug_print_var.php" => true,
                "modifier.escape.php" => true,
                "modifier.regex_replace.php" => true,
                "modifier.replace.php" => true,
                "modifier.spacify.php" => true,
                "modifier.truncate.php" => true,
                "modifiercompiler.cat.php" => true,
                "modifiercompiler.count_characters.php" => true,
                "modifiercompiler.count_paragraphs.php" => true,
                "modifiercompiler.count_sentences.php" => true,
                "modifiercompiler.count_words.php" => true,
                "modifiercompiler.default.php" => true,
                "modifiercompiler.escape.php" => true,
                "modifiercompiler.from_charset.php" => true,
                "modifiercompiler.indent.php" => true,
                "modifiercompiler.lower.php" => true,
                "modifiercompiler.noprint.php" => true,
                "modifiercompiler.string_format.php" => true,
                "modifiercompiler.strip.php" => true,
                "modifiercompiler.strip_tags.php" => true,
                "modifiercompiler.to_charset.php" => true,
                "modifiercompiler.unescape.php" => true,
                "modifiercompiler.upper.php" => true,
                "modifiercompiler.wordwrap.php" => true,
                "outputfilter.trimwhitespace.php" => true,
                "shared.escape_special_chars.php" => true,
                "shared.literal_compiler_param.php" => true,
                "shared.make_timestamp.php" => true,
                "shared.mb_str_replace.php" => true,
                "shared.mb_unicode.php" => true,
                "shared.mb_wordwrap.php" => true,
                "variablefilter.htmlspecialchars.php" => true,
            );
            $iterator = new DirectoryIterator($source);
            foreach ($iterator as $file) {
                if (!$file->isDot()) {
                    $filename = $file->getFilename();
                    if (isset($expected[$filename])) {
                        unset($expected[$filename]);
                    }
                }
            }
            if ($expected) {
                $status = false;
                $message = "FAILED: files missing from libs/plugins: ". join(', ', array_keys($expected));
                if ($errors === null) {
                    echo $message . ".\n";
                } else {
                    $errors['plugins'] = $message;
                }
            } elseif ($errors === null) {
                echo "... OK\n";
            }
        } else {
            $status = false;
            $message = "FAILED: ". SMARTY_PLUGINS_DIR .' is not a directory';
            if ($errors === null) {
                echo $message . ".\n";
            } else {
                $errors['plugins_dir_constant'] = $message;
            }
        }
        
        if ($errors === null) {
            echo "Tests complete.\n";
            echo "</PRE>\n";
        }
    
        return $status;
    }

}

?>