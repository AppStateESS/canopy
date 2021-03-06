<?php

namespace phpws2\Variable;

/**
 * Description of File
 *
 * @author matt
 */
class FileVar extends \phpws2\Variable\StringVar {

    protected $input_type = 'file';

    public function __construct($value = null, $varname = null)
    {
        $this->setRegexpMatch('/^[^|;,!@#$()<>\\"\'`~{}\[\]=+&\^\s\t]+([\w\s\(\)\-]+(\.[\w]+))$/i');
        parent::__construct($value, $varname);
        $this->setLimit(255);
    }

    public function exists()
    {
        return is_file($this->value);
    }

    public function writable()
    {
        return is_writable($this->value);
    }

    public function requireOnce()
    {
        if (!$this->exists()) {
            throw new \Exception(sprintf('File not found: %s', $this->__toString()));
        }

        require_once $this->value;
    }

    public function delete()
    {
        return $this->unlink();
    }

    public function unlink()
    {
        if (!$this->exists()) {
            throw new \Exception(sprintf('File not found: %s', $this->__toString()));
        }

        if (!$this->writable()) {
            throw new \Exception(sprintf('Permissions insufficient to delete file: %s',
                    $this->__toString()));
        }

        unlink($this->value);
    }

}

