<?php
namespace Lybe\Jeeves\Api;
abstract class ApiAbstract
{

    protected function config() {
        return;
    }

    protected function api() {
        return;
    }

    /**
     *
     * check this out
     *
     *
     * This make sure you get all variables you expect, at least with default values, and the right data type.
     * example: $in = _Default($default,$in);
     * @version 2015-05-10
     * @since   2013-09-05
     * @author  Peter Lembke
     * @param $default
     * @param $in
     * @return array

    final protected function _Default(array $default = array(), array $in = array())
    {
        if (is_array($default) === false and is_array($in) === true) {
            return $in;
        }
        if (is_array($default) === true and is_array($in) === false) {
            return $default;
        }

        $answer = array_intersect_key(array_merge($default, $in), $default);

        foreach ($default as $key => $data) {
            if (gettype($answer[$key]) !== gettype($default[$key]) and is_null($default[$key]) === false) {
                $answer[$key] = $default[$key];
            }
        }
        return $answer;
    }*/

    /**
     * Make sure you get all variables you expect, at least with default values, and the right data type.
     * Used by: EVERY function.
     * The $default variables, You can only use: array, string, integer, float, null
     * The $in variables, You can only use: array, string, integer, float
     * @example: $in = _Default($default,$in);
     * @version 2016-01-25
     * @since   2013-09-05
     * @author  Peter Lembke
     * @param $default
     * @param $in
     * @return array
     */
    final protected function _Default(array $default = array(), array $in = array())
    {
        if (is_array($default) === false and is_array($in) === true) {
            return $in;
        }
        if (is_array($default) === true and is_array($in) === false) {
            return $default;
        }

        $answer = $this->_DefaultRecursive($default, $in);
        return $answer;
    }

    final protected function _DefaultRecursive(array $default = array(), array $in = array())
    {
        // On this level: Remove all variables that are not in default. Add all variables that are only in default.
        $answer = array_intersect_key(array_merge($default, $in), $default);

        // Check the data types
        foreach ($default as $key => $data) {
            if (gettype($answer[$key]) !== gettype($default[$key])) {
                if (is_null($default[$key]) === false) {
                    $answer[$key] = $default[$key];
                }
                continue;
            }
            if (is_null($default[$key]) === true and is_null($answer[$key]) === true) {
                $answer[$key] = '';
                continue;
            }
            if (is_array($default[$key]) === false) {
                continue;
            }
            if (count($default[$key]) === 0) {
                continue;
            }
            $answer[$key] = $this->_Default($default[$key], $answer[$key]);
        }

        return $answer;
    }

    protected function sendRawMail($sender, $receiver, $subject, $body)
    {
        try {
            $mail = new \Zend_Mail("UTF-8");
            $mail->setFrom($sender, $sender);
            $mail->addTo($receiver, $receiver);
            $mail->setSubject($subject);
            $mail->setBodyHtml($body); // setBodyText options.
            $mail->send();
        } catch (Exception $e) {
            Mage::log($e->getMassage());
        }
    }

    protected function output(array $out = array()) {
        if (isset($out['message'])) {
            $out['message'] = $this->__($out['message']); // Translate the message
        }
        $outString = json_encode($out);
        echo '<pre>' . $outString . '</pre>';
    }
}