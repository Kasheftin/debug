<?php

error_reporting(E_ERROR);

include("debug.class.php");


$query = "select * from hotels";

DEBUG::setOpt(array("realtime"=>1,"mode"=>"SHORT&SQL"));

DEBUG::logStart(md5($query),$query,"SQL");
sleep(1);
DEBUG::logEnd(md5($query));

DEBUG::log("short debug","SHORT");

DEBUG::log("just a debug",$query);

sleep(1);


echo "\n\n\n\n";

DEBUG::display();

