<?php 

class plain_template implements template
{
    public function miniheader ()
    {
        printf("Queries by type:\n================\n");
    }

    public function minirow ($type, $num, $percent)
    {
        printf("% -20s % -10s [%5s%%] \n", $type, number_format($num, 0, '', ' '), number_format($percent, 2));
    }

    public function minifooter ($total)
    {
        printf("---------------\nTotal: %s queries\n\n\n", number_format($total, 0, '', ' '));
    }

    public function mainheader ()
    {
        printf("Queries by pattern:\n===================\n");
    }

    public function mainrow ($ornum, $num, $percent, $query, $sort = false, $smpl = false)
    {
        if ($sort)
            printf("%d.\t% -10s [%10s] - %s\n", $ornum, number_format($num, 0, '', ' '), number_format($percent, 2), $query);
        else
            printf("%d.\t% -10s [% 5s%%] - %s\n", $ornum, number_format($num, 0, '', ' '), number_format($percent, 2), $query);

        if ($smpl)
            printf("%s\n\n", $smpl);
    }

    public function mainfooter ($total)
    {
        printf("---------------\nTotal: %s patterns", number_format($total, 0, '', ' '));
    }
}


 ?>