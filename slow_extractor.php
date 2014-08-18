<?php 

/**
 * Extracts normalized queries from mysql slow query log one by one
 *
 */
class slow_extractor extends filereader implements query_fetcher
{
    protected $stat = array();

    /**
     * Fetch the next query pattern from stream
     *
     * @return string
     */
    public function get_query ()
    {
        $currstatement = '';

        $fp = $this->fp;

        while (($line = fgets($fp)))
        {
            $line = rtrim($line, "\r\n");

            if (($smth = $this->is_separator($line, $fp))) {
                if (is_array($smth))
                    $this->stat = $smth;

                if ($currstatement !== '') {
                    return array_merge($this->stat, array($currstatement));
                }
            }
            else
            {
                $currstatement .= $line;
            }
        }

        if ($currstatement !== '')
            return array_merge($this->stat, array($currstatement));
        else
            return false;
    }

    protected function is_separator (&$line, $fp)
    {
        // skip server start log lines
        /*
          /usr/sbin/mysqld, Version: 5.0.26-log. started with:
          Tcp port: 3306  Unix socket: /var/lib/mysql/mysqldb/mysql.sock
          Time                 Id Command    Argument
          */
        if (substr($line, -13) == "started with:") {
            fgets($fp); // skip TCP Port: 3306, Named Pipe: (null)
            fgets($fp); // skip Time                 Id Command    Argument
            return true;
        }

        // skip command information
        # Time: 070103 16:53:22
        # User@Host: photo[photo] @ dopey [192.168.16.70]
        # Query_time: 14  Lock_time: 0  Rows_sent: 93  Rows_examined: 3891399

        $linestart = substr($line, 0, 14);

        if (!strncmp($linestart, '# Time: ', 8)
            || !strncmp($line, '# User@Host: ', 13)
        )
            return true;

        if (!strncmp($line, '# Query_time: ', 14)) {
            $matches = array();

            // floating point numbers matching is needed for
            // www.mysqlperformanceblog.com slow query patch
            preg_match('/Query_time: +(\\d*(?:\\.\\d+)?) +Lock_time: +(\\d*(?:\\.d+)?) +Rows_sent: +(\\d*?) +Rows_examined: +(\\d*?)/', $line, $matches);

            // shift the whole matched string element
            // leaving only numbers we need
            array_shift($matches);
            $arr = array(
                'qt'=> array_shift($matches),
                'lt'=> array_shift($matches),
                'rs'=> array_shift($matches),
                're'=> array_shift($matches),
            );
            return $arr;
        }

        if (preg_match('/(?:^use [^ ]+;$)|(?:^SET timestamp=\\d+;$)/', $line))
            return true;

        return false;
    }
}

 ?>