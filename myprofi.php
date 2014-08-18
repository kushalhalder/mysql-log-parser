<?php 

/**
 * Main statistics gathering class
 *
 */
class myprofi
{
    /**
     * Query fetcher class
     *
     * @var mixed
     */
    protected $fetcher;

    /**
     * Top number of queries to output in stats
     *
     * @var integer
     */
    protected $top = null;

    /**
     * Only queries of these types to calculate
     *
     * @var array
     */
    protected $types = null;

    /**
     * Will the input file be treated as CSV formatted
     *
     * @var boolean
     */
    protected $csv = false;

    /**
     * Will the input file be treated as slow query log
     *
     * @var boolean
     */
    protected $slow = false;

    /**
     * Will the statistics include a sample query for each
     * pattern
     *
     * @var boolean
     */
    protected $sample = false;

    /**
     * Field name to sort by
     *
     * @var string
     */
    protected $sort;

    /**
     * Input filename
     */
    protected $filename;

    protected $_queries = array();
    protected $_nums = array();
    protected $_types = array();
    protected $_samples = array();
    protected $_stats = array();

    protected $total = 0;

    /**
     * Set the object that can fetch queries one by one from
     * some storage
     *
     * @param query_fetcher $prov
     */
    protected function set_data_provider (query_fetcher $prov)
    {
        $this->fetcher = $prov;
    }

    /**
     * Set maximum number of queries
     *
     * @param integer $top
     */
    public function top ($top)
    {
        $this->top = $top;
    }

    /**
     * Set array of query types to calculate
     *
     * @param string $types - comma separated list of types
     */
    public function types ($types)
    {
        $types = explode(',', $types);
        $types = array_map('trim', $types);
        $types = array_map('strtolower', $types);
        $types = array_flip($types);

        $this->types = $types;
    }

    /**
     * Set the csv format of an input file
     *
     * @param boolean $csv
     */
    public function csv ($csv)
    {
        $this->csv = $csv;
    }

    /**
     * Set the csv format of an input file
     *
     * @param boolean $slow
     *
     * @return boolean
     */
    public function slow ($slow = null)
    {
        if (is_null($slow))
            return $this->slow;

        return $this->slow = $slow;
    }

    /**
     * Keep one sample query for each pattern
     *
     * @param boolean $sample
     */
    public function sample ($sample)
    {
        $this->sample = $sample;
    }

    /**
     * Set input file
     *
     * @param string $filename
     */
    public function set_input_file ($filename)
    {
        if (!$this->csv && (strcasecmp(".csv", substr($filename, -4)) === 0))
            $this->csv(true);

        $this->filename = $filename;
    }

    public function sortby ($sort)
    {
        $this->sort = $sort;
    }

    /**
     * The main routine so count statistics
     *
     */
    public function process_queries ()
    {
        if ($this->csv) {
            if ($this->slow)
                $this->set_data_provider(new slow_csvreader($this->filename));
            else
                $this->set_data_provider(new csvreader($this->filename));
        }
        elseif ($this->slow)
            $this->set_data_provider(new slow_extractor($this->filename));
        else
            $this->set_data_provider(new extractor($this->filename));

        // counters
        $i = 0;

        // stats arrays
        $queries = array();
        $nums    = array();
        $types   = array();
        $samples = array();
        $stats   = array();

        // temporary assigned properties
        $prefx = $this->types;
        $ex    = $this->fetcher;

        // group queries by type and pattern
        while (($line = $ex->get_query()))
        {
            $stat = false;

            if (is_array($line)) {
                $stat = $line;
                $line = array_pop($stat); // extract statement, it's always the last element of array
            }

            // keep query sample
            $smpl = $line;

            if ('' == ($line = normalize($line))) continue;

            // extract first word to determine query type
            $t    = preg_split("/[\\W]/", $line, 2);
            $type = $t[0];

            if (!is_null($prefx) && !isset($prefx[$type]))
                continue;

            $hash = md5($line);

            // calculate query by type
            if (!array_key_exists($type, $types))
                $types[$type] = 1;
            else
                $types[$type]++;

            // calculate query by pattern
            if (!array_key_exists($hash, $queries)) {
                $queries[$hash] = $line; // patterns
                $nums[$hash]    = 1; // pattern counts
                $stats[$hash]   = array(); // slow query statistics

                if ($this->sample)
                    $samples[$hash] = $smpl; // patterns samples
            }
            else
            {
                $nums[$hash]++;
            }

            // calculating statistics
            if ($stat) {
                foreach ($stat as $k=> $v)
                {
                    if (isset($stats[$hash][$k])) {
                        // sum with total
                        $stats[$hash][$k]['t'] += $v;

                        if ($v > $stats[$hash][$k]['m']) {
                            // increase maximum, if the current value is bigger
                            $stats[$hash][$k]['m'] = $v;
                        }
                    }
                    else
                    {
                        // set total and maximum values
                        $stats[$hash][$k] = array(
                            't'=> $v,
                            'm'=> $v
                        );
                    }
                }
            }

            $i++;
        }

        $stats2 = array();
        if ($this->slow) {
            foreach ($stats as $hash => $col)
            {
                foreach ($col as $k => $v)
                {
                    $stats2[$hash][$k . '_total'] = $v['t'];
                    $stats2[$hash][$k . '_avg']   = $v['t'] / $nums[$hash];
                    $stats2[$hash][$k . '_max']   = $v['m'];
                }
            }
        }

        $stats = $stats2;

        if ($this->sort)
            uasort($stats, array($this, 'cmp'));
        else
            arsort($nums);

        arsort($types);

        if (!is_null($this->top)) {
            if ($this->sort)
                $stats = array_slice($stats, 0, $this->top);
            else
                $nums = array_slice($nums, 0, $this->top);

        }

        $this->_queries = $queries;
        $this->_nums    = $nums;
        $this->_types   = $types;
        $this->_samples = $samples;
        $this->_stats   = $stats;

        $this->total = $i;
    }

    public function get_types_stat ()
    {
        return new ArrayIterator($this->_types);
    }

    protected function cmp ($a, $b)
    {
        $f = $a[$this->sort];
        $s = $b[$this->sort];

        return ($f < $s) ? 1 : ($f > $s ? -1 : 0);
    }

    public function get_pattern_stats ()
    {
        $stat = array();

        if ($this->sort)
            $tmp =& $this->_stats;
        else
            $tmp =& $this->_nums;

        if (list($h, $n) = each($tmp)) {
            if ($this->sort) {
                $stat = $n;
                $n    = $this->_nums[$h];
            }

            if ($this->sample)
                return array($n, $this->_queries[$h], $this->_samples[$h], $stat);
            else
                return array($n, $this->_queries[$h], false, $stat);
        }
        else
            return false;
    }

    public function total ()
    {
        return $this->total;
    }
}



 ?>