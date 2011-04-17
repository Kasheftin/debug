<?php

/* 
	Debug class by Kasheftin

		v. 0.12 (2010.12.01)
			- Debug mode: all, important, none added.
		v. 0.11 (2010.11.23)
			- Method log changed: now message might be an array, not only a string.
		v. 0.10 (2010.11.23)
			- Tracking start.
*/	

class Debug
{
	static protected $oInstance = null;
	protected $start = null;
	protected $end = null;
	protected $dta = array();		// That is for assoc-dt-pointers.
	protected $dts = array();		// That is for default (numeric) dt-pointers.
	protected $log = array();
	protected $opts = array();
	protected $modes = array("all","important");
 
	static public function getInstance() 
	{
		if (isset(self::$oInstance) and (self::$oInstance instanceof self)) 
		{
			return self::$oInstance;
		} 
		else 
		{
			self::$oInstance= new self();
			return self::$oInstance;
		}
	}
	public function __clone() { }

	protected function __construct() 
	{
		$this->opts["debug"] = "text"; 
		$this->opts["direct_output"] = false;
		$this->opts["mode"] = "all";
	}

	static public function set()
	{
		$o = self::getInstance();

		$args = func_get_args();
		if (count($args) == 2)
			$args = array(0=>array($args[0]=>$args[1]));
		elseif (count($args) == 1 && is_array($args[0])) { }
		else return self::log("Incorrect opts",__METHOD__);

		foreach($args[0] as $i => $v)
			$o->opts[$i] = $v;
	}

	static public function begin()
	{
		$o = self::getInstance();
		$o->start = $o->t();
	}

	static public function start()
	{
		$o = self::getInstance();
		$o->start = $o->t();
	}

	static public function end()
	{
		$o = self::getInstance();
		$o->end = $o->t();
	}

	static public function finish()
	{
		$o = self::getInstance();
		$o->end = $o->t();
	}

	static public function log()
	{
		$o = self::getInstance();

		$args = func_get_args();
		if (count($args) == 2)
		{
			$message = $args[0];
			$type = $args[1];
		}
		elseif (count($args) == 1 && is_array($args[0]))
		{
			foreach($args[0] as $i => $v)
				${$i} = $v;
		}
		elseif (count($args) == 1)
		{
			$message = $args[0];
		}
		elseif (count($args) == 3)
		{
			if (in_array($args[2],$o->modes))
				$mode = $args[2];
			else
				$dt_pointer = $args[2];
			$message = $args[0];
			$type = $args[1];
		}
		else return self::log("Incorrect opts",__METHOD__);

		$ar = array("message"=>$message,"type"=>$type,"time"=>$o->t(),"mode"=>($mode?$mode:$o->modes[0]));

		if ($dt_pointer || $use_dt_pointer)
		{
			if ($dt_pointer)
			{
				$ar["time_start"] = $o->dta[$dt_pointer];
				unset($o->dta[$dt_pointer]);
			}
			else
				$ar["time_start"] = array_pop($o->dts);
		}

		$o->log[] = $ar;

		if ($o->opts["direct_output"] && ($o->opts["mode"] == "all" || ($o->opts["mode"] == "important" && $ar["mode"] == "important")))
			$o->direct_out($ar);
	}

	static public function log_start($dt_pointer=null)
	{
		$o = self::getInstance();
		if ($dt_pointer)
			$o->dta[$dt_pointer] = $o->t();
		else
			$o->dts[] = $o->t();
	}

	static public function log_end($message,$type,$dt_pointer=null)
	{
		self::log(array("message"=>$message,"type"=>$type,"dt_pointer"=>$dt_pointer,"use_dt_pointer"=>1));
	}

	static public function out($format=null)
	{
		$o = self::getInstance();

		$out = "FULLTIME: " . ($o->end - $o->start);

		if (!$o->opts["direct_output"])
		{
			foreach($o->log as $ar)
				$out .= $o->out_one($ar);
		}

		if ($format == "html" || $o->opts["format"] == "html")
			$out = "<div style='margin: 10px; border: 1px solid #dedede; padding: 5px; font-size: 0.85em; text-align: left;'><strong>DEBUG INFO</strong><pre>\n\n" . $out . "\n\n</pre></div>";
		else
			$out = "\n\n<!--\n=====DEBUG INFO=========\n\n" . $out . "\n\n=====DEBUG INFO END=====\n-->\n";

		return $out;
	}

	protected function direct_out($ar)
	{
		$out = $this->out_one($ar);
		if ($this->opts["format"] == "html")
			$out = "<pre>" . $out . "\n</pre>\n";
		else
			$out .= "\n" . $out . "\n";
		echo $out;
	}

	protected function out_one($ar)
	{
		$out = "\n" . ($ar[type]?"[" . $ar[type] . "] ":"") . ($ar[time_start]?"[" . sprintf("%01.4f",$ar[time]-$ar[time_start]) . "] ":"");
		if (is_array($ar[message]))
			$out .= preg_replace("/[\r\t\n]/","",print_r($ar[message],1));
		else
			$out .= $ar[message];
		return $out;
	}

	protected function t()
	{
		list($usec, $sec) = explode(" ",microtime()); return ((float)$usec + (float)$sec); 
	}
}

