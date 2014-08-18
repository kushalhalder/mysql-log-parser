<?php 

interface template
{
    public function miniheader ();

    public function minirow ($type, $num, $percent);

    public function minifooter ($total);

    public function mainheader ();

    public function mainrow ($ornum, $num, $percent, $query, $sort = false, $smpl = false);

    public function mainfooter ($total);
}

 ?>