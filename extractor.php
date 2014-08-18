<?php 

/**
 * Extracts normalized queries from mysql query log one by one
 *
 */
class extractor extends filereader implements query_fetcher
{
    /**
     * Fetch the next query pattern from stream
     *
     * @return string
     */
    public function get_query ()
    {
        static $newline;

        $return  = $newline;
        $newline = null;

        $fp = $this->fp;

        while (($line = fgets($fp)))
        {
            $line = rtrim($line, "\r\n");

            // skip server start log lines
            if (substr($line, -13) == "started with:") {
                fgets($fp); // skip TCP Port: 3306, Named Pipe: (null)
                fgets($fp); // skip Time                 Id Command    Argument
                continue;
            }

            $matches = array();
            if (preg_match("/^(?:\\d{6} {1,2}\\d{1,2}:\\d{2}:\\d{2}|\t)\\s+\\d+\\s+(\\w+)/", $line, $matches)) {
                // if log line
                $type = $matches[1];
                switch ($type)
                {
                    case 'Query':
                        if ($return) {
                            $newline = ltrim(substr($line, strpos($line, "Q") + 5), " \t");
                            break 2;
                        }
                        else
                        {
                            $return = ltrim(substr($line, strpos($line, "Q") + 5), " \t");
                            break;
                        }
                    case 'Execute':
                        if ($return) {
                            $newline = ltrim(substr($line, strpos($line, ']') + 1), " \t");
                            break 2;
                        }
                        else
                        {
                            $return = ltrim(substr($line, strpos($line, ']') + 1), " \t");
                            break;
                        }
                    default:
                        if ($return)
                            break 2;
                        else
                            break;
                }
            }
            else
            {
                $return .= $line;
            }
        }

        return ($return === '' || is_null($return) ? false : $return);
    }
}

 ?>