<?php 


/**
 * Read mysql slow query log in csv format (as of mysql 5.1 it by default)
 *
 */
class slow_csvreader extends filereader implements query_fetcher
{
    /**
     * Fetch next query from csv file
     *
     * @return string - or FALSE on file end
     */
    public function get_query ()
    {
        while (false !== ($data = fgetcsv($this->fp)))
        {
            if (!isset($data[10]))
                continue;

            $query_time    = self::time_to_int($data[2]);
            $lock_time     = self::time_to_int($data[3]);
            $rows_sent     = $data[4];
            $rows_examined = $data[5];

            $statement = str_replace(array("\\\\", '\\"'), array("\\", '"'), $data[10]);

            // cut statement id from prefix of prepared statement

            return array(
                'qt'=> $query_time,
                'lt'=> $lock_time,
                'rs'=> $rows_sent,
                're'=> $rows_examined, $statement
            );
        }
        return false;
    }

    /**
     * Converts time value in format H:i:s into integer
     * representation of number of total seconds
     *
     * @param string $time
     *
     * @return integer
     */
    protected static function time_to_int ($time)
    {
        list($h, $m, $s) = explode(':', $time);
        return ($h * 3600 + $m * 60 + $s);
    }
}

 ?>