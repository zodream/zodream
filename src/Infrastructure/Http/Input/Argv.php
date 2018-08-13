<?php
namespace Zodream\Infrastructure\Http\Input;

trait Argv {
    protected function createArgv() {
        // SET ARGV TO GET PARAM, IF NO '=' , VALUE IS '', YOU CAN USE IS_NULL JUDGE
        $args = $_SERVER['argv'];
        if (empty($args)) {
            return [];
        }
        return $this->getArguments($args);
    }

    /**
     * 转换
     * @param $args
     * @return array
     */
    protected function getArguments($args) {
        if (isset($args[0])) {
            array_shift($args);
        }
        $endOfOptions = false;

        $ret = array (
            'commands' => array(),
            'options' => array(),
            'flags'    => array(),
            'arguments' => array(),
        );

        while ($arg = array_shift($args)) {

            // if we have reached end of options,
            //we cast all remaining argvs as arguments
            if ($endOfOptions) {
                $ret['arguments'][] = $arg;
                continue;
            }

            // Is it a command? (prefixed with --)
            if ( substr($arg, 0, 2 ) === '--' ) {

                // is it the end of options flag?
                if (!isset($arg[3])) {
                    $endOfOptions = true;; // end of options;
                    continue;
                }

                $value = "";
                $com   = substr( $arg, 2 );

                // is it the syntax '--option=argument'?
                if (strpos($com,'=')) {
                    list($com, $value) = explode("=",$com,2);
                }


                // is the option not followed by another option but by arguments
                elseif (strpos($args[0],'-') !== 0) {
                    while (!empty($args) && strpos($args[0],'-') !== 0) {
                        $value .= array_shift($args).' ';
                    }
                    $value = rtrim($value,' ');
                }

                $ret['options'][$com] = !empty($value) ? $value : true;
                continue;

            }

            // Is it a flag or a serial of flags? (prefixed with -)
            if ( substr( $arg, 0, 1 ) === '-' ) {
                for ($i = 1; isset($arg[$i]) ; $i++) {
                    $ret['flags'][] = $arg[$i];
                }
                continue;
            }

            // finally, it is not option, nor flag, nor argument
            $ret['commands'][] = $arg;
            continue;
        }

        if (!count($ret['options']) && !count($ret['flags'])) {
            $ret['arguments'] = array_merge($ret['commands'], $ret['arguments']);
            $ret['commands'] = array();
        }
        return $ret;
    }
}