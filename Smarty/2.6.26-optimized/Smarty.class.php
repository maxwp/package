<?php
/**
 * Project:     Smarty: the PHP compiling template engine
 * File:        Smarty.class.php
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
 * @copyright 2001-2005 New Digital Group, Inc.
 * @author Monte Ohrt <monte at ohrt dot com>
 * @author Andrei Zmievski <andrei@php.net>
 * @package Smarty
 * @version 2.6.26
 */

/**
 * set SMARTY_DIR to absolute path to Smarty library files.
 * if not defined, include_path will be used. Sets SMARTY_DIR only if user
 * application has not already defined it.
 */
if (!defined('SMARTY_DIR')) {
    define('SMARTY_DIR', __DIR__ . '/');
}

if (!defined('SMARTY_CORE_DIR')) {
    define('SMARTY_CORE_DIR', __DIR__ . '/internals/');
}

/**
 * @package Smarty
 */
class Smarty {

    /**
     * The directory where compiled templates are located.
     *
     * @var string
     */
    public $compile_dir     =  'templates_c';

    /**
     * An array of directories searched for plugins.
     *
     * @var array
     */
    var $plugins_dir     =  array('plugins');

    /**
     * The left delimiter used for the template tags.
     *
     * @var string
     */
    var $left_delimiter  =  '{|';

    /**
     * The right delimiter used for the template tags.
     *
     * @var string
     */
    var $right_delimiter =  '|}';

    /**
     * The order in which request variables are registered, similar to
     * variables_order in php.ini E = Environment, G = GET, P = POST,
     * C = Cookies, S = Server
     *
     * @var string
     */
    var $request_vars_order    = 'EGPCS';

    /**
     * where assigned template vars are kept
     *
     * @var array
     */
    var $_tpl_vars             = array();

    /**
     * stores run-time $smarty.* vars
     *
     * @var null|array
     */
    var $_smarty_vars          = null;

    /**
     * keeps track of sections
     *
     * @var array
     */
    var $_sections             = array();

    /**
     * keeps track of foreach blocks
     *
     * @var array
     */
    var $_foreach              = array();

    /**
     * keeps track of tag hierarchy
     *
     * @var array
     */
    var $_tag_stack            = array();

    /**
     * Smarty version number
     *
     * @var string
     */
    var $_version              = '2.6.27-optimized';

    /**
     * registered objects
     *
     * @var array
     */
    var $_reg_objects           = array();

    /**
     * table keeping track of plugins
     *
     * @var array
     */
    var $_plugins              = array(
    'modifier'      => array(),
    'function'      => array(),
    'compiler'      => array(),
    );

    /**
     * assigns values to template variables
     *
     * @param array|string $tpl_var the template variable name(s)
     * @param mixed $value the value to assign
     */
    public function assign($tpl_var, $value = null) {
        if (is_object($value)) {
            // если объект
            $this->_reg_objects[$tpl_var] = array($value, array(), true, array());
        } else {
            // иначе если массив или строка
            if (is_array($tpl_var)) {
                foreach ($tpl_var as $key => $val) {
                    if ($key != '') {
                        $this->_tpl_vars[$key] = $val;
                    }
                }
            } elseif ($tpl_var != '') {
                $this->_tpl_vars[$tpl_var] = $value;
            }
        }
    }

    /**
     * Массовая передача переменных в smarty, без вызова поштучных assing().
     * Особенно актуально, когда нужно передать около 10 000 переменных.
     *
     * Параметр $merge используется если нужно дописать переменные.
     *
     * @param array $a
     * @param bool $merge
     */
    public function assignArray($a, $merge = true) {
        if ($merge) {
            $this->_tpl_vars = array_merge($this->_tpl_vars, $a);
        } else {
            // массовая замена
            $this->_tpl_vars = $a;
        }
    }

    /**
     * clear the given assigned template variable.
     *
     * @param string $tpl_var the template variable to clear
     */
    public function clear_assign($tpl_var) {
        // если объект
        unset($this->_reg_objects[$tpl_var]);

        // если строка или массив
        if (is_array($tpl_var)) {
            foreach ($tpl_var as $curr_var) {
                unset($this->_tpl_vars[$curr_var]);
            }
        } else {
            unset($this->_tpl_vars[$tpl_var]);
        }
    }

    /**
     * Registers custom function to be used in templates
     *
     * @param string $function the name of the template function
     * @param string $function_impl the name of the PHP function to register
     */
    public function register_function($function, $function_impl, $cacheable=true, $cache_attrs=null) {
        $this->_plugins['function'][$function] = array($function_impl, null, null, false, $cacheable, $cache_attrs);
    }

    /**
     * Unregisters custom function
     *
     * @param string $function name of template function
     */
    public function unregister_function($function) {
        unset($this->_plugins['function'][$function]);
    }

    /**
     * Registers modifier to be used in templates
     *
     * @param string $modifier name of template modifier
     * @param string $modifier_impl name of PHP function to register
     */
    public function register_modifier($modifier, $modifier_impl) {
        $this->_plugins['modifier'][$modifier] =
        array($modifier_impl, null, null, false);
    }

    /**
     * Unregisters modifier
     *
     * @param string $modifier name of template modifier
     */
    public function unregister_modifier($modifier) {
        unset($this->_plugins['modifier'][$modifier]);
    }

    /**
     * clear all the assigned template variables.
     */
    public function clear_all_assign() {
        $this->_tpl_vars = array();
        $this->_reg_objects = array();
    }

    /**
     * Returns an array containing template variables
     *
     * @param string $name
     * @param string $type
     * @return array
     */
    function &get_template_vars($name=null) {
        if(!isset($name)) {
            return $this->_tpl_vars;
        } elseif(isset($this->_tpl_vars[$name])) {
            return $this->_tpl_vars[$name];
        } else {
            // var non-existant, return valid reference
            $_tmp = null;
            return $_tmp;
        }
    }

    /**
     * trigger Smarty error
     *
     * @param string $error_msg
     * @param integer $error_type
     */
    public function trigger_error($error_msg, $error_type = E_USER_WARNING) {
        trigger_error("Smarty error: $error_msg", $error_type);
    }

    /**
     * executes & returns or displays the template results
     *
     * @param string $resource_name
     * @param string $cache_id
     * @param string $compile_id
     * @param boolean $display
     */
    public function fetch($resource_name, $exception = false) {
        $this->_exception = $exception;

        $code = $this->_getFileContentFunction($resource_name);

        // if empty html code
        if (!$code) {
            return $code;
        }

        // if no smarty
        if (!substr_count($code, $this->left_delimiter)) {
            return $code;
        }

        $_smarty_old_error_level = error_reporting(error_reporting() & ~E_NOTICE);

        $resource_compiled_path = $this->_get_auto_filename($this->compile_dir, $resource_name);

        ob_start();

        // есть скомпилированное?
        $ok = $this->_is_compiled($resource_name, $resource_compiled_path);

        // если нет - пробуем скомпилировать
        if (!$ok) {
            $ok = $this->_compile_resource($resource_name, $resource_compiled_path);
        }

        // есть что вызывать?
        if ($ok) {
            $code = $this->_getFileContentFunction($resource_compiled_path);
            eval($code);
        }

        $_smarty_results = ob_get_contents();
        ob_end_clean();

        error_reporting($_smarty_old_error_level);

        // @todo wtf
        if ($this->_exceptionCode && $this->_exception) {
            throw new ServiceUtils_Exception('error_smarty');
        }

        return $_smarty_results;
    }


    private function _getFileContentFunction($filename) {
        if (isset($this->_fileContentArray[$filename])) {
            return $this->_fileContentArray[$filename];
        }

        $code = file_get_contents($filename);
        if (str_starts_with($code, '<?php')) {
            $code = substr($code, 5);
        }

        $this->_fileContentArray[$filename] = $code;

        return $code;
    }

    private $_fileContentArray = [];

    /**
     * get filepath of requested plugin
     *
     * @param string $type
     * @param string $name
     * @return string|false
     */
    public function _get_plugin_filepath($type, $name) {
        $_params = array('type' => $type, 'name' => $name);
        require_once(SMARTY_CORE_DIR . 'core.assemble_plugin_filepath.php');
        return smarty_core_assemble_plugin_filepath($_params, $this);
    }

    /**
     * Test if resource needs compiling
     *
     * @param string $resource_name
     * @param string $compile_path
     *
     * @return boolean
     */
    private function _is_compiled($resource_name, $compile_path) {
        if (isset($this->_isCompliledArray[$resource_name])) {
            return $this->_isCompliledArray[$resource_name];
        }

        $m2 = @filemtime($compile_path);

        // если скомпилированного файла нет - то нихуя дальше не проверяем,
        if (!$m2) {
            $m1 = false;
        } else {
            $m1 = @filemtime($resource_name);
        }

        if ($m1 === false || $m2 === false || $m1 >= $m2) {
            $this->_isCompliledArray[$resource_name] = false;
            return false;
        }

        $this->_isCompliledArray[$resource_name] = true;
        return true;
    }

    private $_isCompliledArray = [];

    /**
     * Compile the template
     *
     * @param string $resource_name
     * @param string $compile_path
     *
     * @return boolean
     */
    private function _compile_resource($resource_name, $compile_path) {
        $_source_content = file_get_contents($resource_name);

        $_compiled_content = $this->_compile_source($resource_name, $_source_content);
        if ($_compiled_content) {
            file_put_contents(
                $compile_path,
                $_compiled_content,
                LOCK_EX
            );

            return true;
        }

        return false;
    }

    /**
     * compile the given source
     *
     * @param string $resource_name
     * @param string $source_content
     * @param string $compiled_content
     * @return boolean
     */
    private function _compile_source($resource_name, &$source_content) {

        require_once(__DIR__.'/Smarty_Compiler.class.php');
        $smarty_compiler = new Smarty_Compiler();

        $smarty_compiler->plugins_dir       = $this->plugins_dir;
        $smarty_compiler->left_delimiter    = $this->left_delimiter;
        $smarty_compiler->right_delimiter   = $this->right_delimiter;
        $smarty_compiler->_version          = $this->_version;
        $smarty_compiler->_exception        = &$this->_exception;
        $smarty_compiler->_reg_objects      = &$this->_reg_objects;
        $smarty_compiler->_plugins          = &$this->_plugins;
        $smarty_compiler->_tpl_vars         = &$this->_tpl_vars;

        $data =  $smarty_compiler->_compile_file($resource_name, $source_content);

        $this->_exceptionCode = $smarty_compiler->_Exception2;

        return $data;

    }

    /**
     * Remove starting and ending quotes from the string
     *
     * @param string $string
     * @return string
     */
    function _dequote($string) {
        if ((substr($string, 0, 1) == "'" || substr($string, 0, 1) == '"') &&
        substr($string, -1) == substr($string, 0, 1))
        return substr($string, 1, -1);
        else
        return $string;
    }

    /**
     * get a concrete filename for automagically created content
     *
     * @param string $auto_base
     * @param string $auto_source
     */
    private function _get_auto_filename($auto_base, $auto_source) {
        return $auto_base . DIRECTORY_SEPARATOR.hash("fnv1a64", $auto_source).'.php';
    }

    /**
     * unlink a file, possibly using expiration time
     *
     * @param string $resource
     * @param integer $exp_time
     */
    function _unlink($resource, $exp_time = null) {
        if (isset($exp_time)) {
            if(time() - @filemtime($resource) >= $exp_time) {
                return @unlink($resource);
            }
        } else {
            return @unlink($resource);
        }
    }

    /**
     * trigger Smarty plugin error
     *
     * @param string $error_msg
     * @param string $tpl_file
     * @param integer $tpl_line
     * @param string $file
     * @param integer $line
     * @param integer $error_type
     */
    function _trigger_fatal_error($error_msg, $tpl_file = null, $tpl_line = null, $file = null, $line = null, $error_type = E_USER_ERROR) {
        if(isset($file) && isset($line)) {
            $info = ' ('.basename($file).", line $line)";
        } else {
            $info = '';
        }
        if (isset($tpl_line) && isset($tpl_file)) {
            $this->trigger_error('[in ' . $tpl_file . ' line ' . $tpl_line . "]: $error_msg$info", $error_type);
        } else {
            $this->trigger_error($error_msg . $info, $error_type);
        }
    }

    function getException () {
        return $this->_exception;
    }

    protected $_exception = false;
    protected $_exceptionCode = false;

}