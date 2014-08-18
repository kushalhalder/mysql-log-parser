<?php 

/**
 * Interface for all query fetchers
 *
 */
interface query_fetcher
{
    /**
     * Get next query in the flow
     *
     */
    public function get_query ();
}

 ?>