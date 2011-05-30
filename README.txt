DEBUG is a dummy logger class, singleton, that stores any data and displays it all at the end. There're different types of data and it's possible to display debug data in realtime.


Basic example:
<?php
include("debug.class.php");
...
DEBUG::log("Some debug");
DEBUG::log("Another debug");
...
DEBUG::display();
?>


Example with multiple strings and arrays in one debug:
<?php
DEBUG::log("One string","Another",$some_array,...,array("var"=>"value"));
?>


Example with timings:
<?php
$query = "select * from articles"; // Some heavy query
$id = md5($query); // Any identifier

DEBUG::logStart($id,$query);
mysql_query($query);
DEBUG::logEnd($id);

DEBUG::display(); // displays $query string and time spent between logStart and logEnd
?>


Example with different types of debug info:
<?php
DEBUG::log("Some very long debug string...","LONG");
DEBUG::log("This is short debug","SHORT");

DEBUG::display(); // displays both long and short strings
DEBUG::setOpt("mode","LONG");
DEBUG::display(); // displays only long string
DEBUG::setOpt("mode","SHORT");
DEBUG::display(); // displays only short string
?>


Example with realtime display (with ob_flush and flush):
<?php
DEBUG::setOpt("realtime",1);
DEBUG::log("some debug"); // displays this string immediately
sleep(1);
DEBUG::log("another debug"); // and this also
?>


Synonyms:
setOpt - setOpt,setOpts,set_opt,set_opts,so
log - log,l
logStart - logStart,log_start,ls
logEnd - logEnd,log_end,le

