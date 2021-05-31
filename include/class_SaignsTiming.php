<?

class SaignsTiming
{
    static $START = array();
    static $DATA = array();

    /**
     * Get the current nanotime
     * @return number
     */
    static function nanotime()
    {
        list ($usec, $sec) = explode(" ", microtime());
        return ((float) $usec + (float) $sec);
    }

    /**
     * Mark the beginning of a process
     * 
     * @param unknown $name
     */
    static function start($name)
    {
        SaignsTiming::$START[$name] = array(
            "start" => SaignsTiming::nanotime(),
            "trace" => function_exists("callstack")?callstack():"",
        );
    }

    /**
     * Mark the end of a process
     * 
     * @param unknown $name
     * @return number
     */
    static function end($name)
    {
        if ( !isset(SaignsTiming::$START[$name]) )
        {
            return - 1;
        }

        $time = (SaignsTiming::nanotime() - SaignsTiming::$START[$name]["start"]) * 1000.0;

        SaignsTiming::$DATA[] = array(
            "name" => $name,
            "time" => round($time,2),
            "trace" => SaignsTiming::$START[$name]["trace"]
        );

        return $time;
    }

    /**
     * Print the status of the timings
     * 
     * @param boolean $bTrace to print the callstacks, too
     */
    static function print_status($bTrace = FALSE)
    {
        echo ("<pre>");
        echo ("<table>");
        $counter = 1;
        foreach (SaignsTiming::$DATA as $entry) {
            echo ("<tr>");
            echo ("<td style=\"vertical-align: top; width: 1%;\">" . ($counter ++) . "</td>");
            echo ("<td style=\"vertical-align: top; width: 1%; padding-left: 10px;\">" . sprintf("%0.2f", $entry['time']) . "</td>");
            echo ("<td style=\"vertical-align: top; padding-left: 10px;\">" . $entry['name'] . "");
            if ($bTrace) {
                echo ("<br>" . $entry['trace'] . "");
            }
            echo ("</td>");
            echo ("</tr>");
        }
        echo ("</table>");
        echo ("</pre>");
    }
}

?>