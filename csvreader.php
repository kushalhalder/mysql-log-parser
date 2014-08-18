<?php 

/**
 * Read mysql query log in csv format (as of mysql 5.1 it by default)
 *
 */
class csvreader extends filereader implements query_fetcher
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
            if ((!isset($data[4])) || (($data[4] !== "Query") && ($data[4] !== "Execute")) || (!$data[5]))
                continue;

            // cut statement id from prefix of prepared statement
            $d5    = $data[5];
            $query = ('Execute' == $data[4] ? substr($d5, strpos($d5, ']') + 1) : $d5);

            return str_replace(array("\\\\", '\\"'), array("\\", '"'), $query);
        }
        return false;
    }
}

 ?>