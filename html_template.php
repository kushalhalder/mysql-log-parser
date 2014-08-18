<?php 

class html_template implements template
{
    public function miniheader ()
    {
        printf('<html><head><title>MyProfi Report</title>
<style type="text/css">
	* { font-size: 10px; font-family: Verdana }
	thead * {font-weight:bold}
</style></head><body>');
        printf('<table border="1"><thead><tr><td colspan="3">Queries by type:</td></tr></thead><tbody>');
    }

    public function minirow ($type, $num, $percent)
    {
        printf("<tr><td>%s</td><td>%s</td><td>%s%%</td></tr>", htmlspecialchars($type), number_format($num, 0, '', ' '), number_format($percent, 2));
    }

    public function minifooter ($total)
    {
        printf('</tbody><tfoot><tr><td colspan="4">Total: %s queries</td></tr></tfoot></table>', number_format($total, 0, '', ' '));
    }

    public function mainheader ()
    {
        printf('<table border="1"><thead><tr><td colspan="4">Queries by pattern:</td></tr>');
        printf('<tr><td>#</td><td>Qty</td><td>%%</td><td>Query</td></tr></thead><tbody>');
    }

    public function mainrow ($ornum, $num, $percent, $query, $sort = false, $smpl = false)
    {
        if ($sort)
            printf('<tr><td>%d</td><td>%s</td><td>%s</td><td>%s</td></tr>', $ornum, $num, $percent, htmlspecialchars($query));
        else
            printf('<tr><td>%d</td><td>%s</td><td>%s%%</td><td>%s</td></tr>', $ornum, $num, $percent, htmlspecialchars($query));

        if ($smpl)
            printf('<tr><td colspan="4"><textarea style="width:100%%" onClick="javascript:this.focus();this.select();">%s</textarea></td></tr>', htmlspecialchars($smpl));
    }

    public function mainfooter ($total)
    {
        printf('</tbody><tfoot><tr><td colspan="4">Total: %s patterns</td></tr></tfoot></table></body></html>', number_format($total, 0, '', ' '));
    }
}

 ?>