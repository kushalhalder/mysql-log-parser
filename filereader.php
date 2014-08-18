<?php 

/**
 * General file reader class
 *
 */
abstract class filereader
{
    /**
     * File pointer
     *
     * @var resource
     */
    public $fp;

    /**
     * Attempts to open a file
     * Dies on failure
     *
     * @param string $filename
     */
    public function __construct ($filename)
    {
        if (false === ($this->fp = @fopen($filename, "rb"))) {
            doc('Error: cannot open input file ' . $filename);
        }
    }

    /**
     * Close file on exit
     *
     */
    public function __destruct ()
    {
        if ($this->fp)
            fclose($this->fp);
    }
}

 ?>