<?php

/* 
	Debug class by Kasheftin

		v. 0.13 (2011.05.29)
			DEBUG::log("Yo lo lo!!!",$ar1,$ar2); - displays message, then print_r($ar1) then print_r($ar2)

			DEBUG::logStart(md5($query),$query,$opts);
			DEBUG::logEnd(md5($query),"ok we've done this query"); - displays timing, then $query, then $opts, and then the last message

			DEBUG::log("Very long debug string .....","LONG");
			DEBUG::log("Short debug","SHORT"); - by default (in ALL mode) displays all logs, in LONG mode displays only the first, in SHORT mode displays only the second one

			DEBUG::log("Very long debug",$ar1,$ar2,$ar3,"LONG"); - also should work

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
	
	protected $data = array();		// Debugs are stored here
	protected $data_timings = array();	// Timings are stored here

	protected $opts = array(
		"mode" => "SHORT|IMPORTANT|MAJOR",					// ALL, SHORT, LONG, IMPORTANT, and so on. + NOMODE - when MODE is not specified. Can be combined with | or comma, eg. SHORT|SQL
		"modes" => "ALL,SHORT,LONG,IMPORTANT,MINOR,MAJOR,SQL,ERROR,REQ",	// List of all available modes
		"realtime" => 0,							// direct output to stdout with ob_flush and flush is turned off by default
		"logfile" => null,							// if logfile path is set, all debug out will be copied there
	);

	protected $synonyms = array(
		"ls,logstart,log_start" => "logstart_call",
		"le,logend,log_end" => "logend_call",
		"l,log" => "log_call",
		"so,setopt,setopts,set_opt,set_opts,setconfig,set_config" => "setopt_call",
		"clean_log,cleanlog,cl" => "clean_log",
	);


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


	static public function __callStatic($m,$a)
	{
		$o = self::getInstance();
		$m = $o->find_method($m);
		if (!method_exists($o,$m))
			throw new Exception("Method " . $m . " doesn't exist");
		$o->$m($a);
	}


	static public function display($id = null)
	{
		$o = self::getInstance();

		$out = "";
		if (isset($id) && $o->opts["realtime"]) 
			$out = $o->get_logline($id);
		elseif (!isset($id)) 
			$out = $o->display_logblock();

		if ($out)
		{
			echo $out;
			ob_flush();
			flush();
			if ($o->opts["logfile"])
				$o->write2log($out);
		}
	}


	protected function clean_log()
	{
		if ($this->opts["logfile"])
		{
			@unlink($this->opts["logfile"]);
		}
	}


	protected function setopt_call($args)
	{
		if (count($args) == 2 && is_string($args[0]))
			$args = array(0=>array($args[0]=>$args[1]));
		elseif (count($args) == 1 && is_array($args[0])) { }
		else return self::log("Incorrect opts",$args);
		foreach($args[0] as $i => $v)
			$this->opts[$i] = $v;
	}


	protected function find_method($m)
	{
		foreach($this->synonyms as $i => $v)
			if (stripos(',,'.$i.',',','.$m.','))
				return $v;
		return $m;
	}


	protected function parse_mode($v)
	{
		$out = array();
		$vals = preg_split("/[&,|]/",$v);
		foreach($vals as $val)
			if (stripos(',,'.$this->opts["modes"].',',','.$val.','))
				$out[$val] = 1;
			else
				return array();
		return $out;
	}


	protected function log_call($a)
	{
		// The last parameter might be MODE
		if ($mode = $this->parse_mode(end($a)))
			array_pop($a);

		$id = count($this->data);
		$this->data[$id] = array("dt"=>$this->t(),"mode"=>$mode);

		foreach($a as $v)
			$this->data[$id]["data"][] = $v;
		
		self::display($id);
	}


	protected function logstart_call($a)
	{
		// The first parameter MUST be some ID
		$id = array_shift($a);

		// The last parameter might be MODE
		if ($mode = $this->parse_mode(end($a)))
			array_pop($a);

		if (!$id) return self::log("Incorrect opts, log id not found",$a);

		$this->data_timings[$id] = array("dt_start"=>$this->t(),"mode"=>$mode);

		foreach($a as $v)
			$this->data_timings[$id]["data"][] = $v;
	}


	protected function logend_call($a)
	{
		// The first parameter MUST be some ID
		$id = array_shift($a);

		// The last parameter might be MODE
		if ($mode = $this->parse_mode(end($a)))
			array_pop($a);

		if (!$id) return self::log("Incorrect opts, log id not found",$a);

		$this->data_timings[$id]["dt_end"] = $this->t();
		
		foreach($a as $v)
			$this->data_timings[$id]["data"][] = $v;
		
		foreach($mode as $i => $v)
			$this->data_timings[$id]["mode"][$i] = $v;

		$data_id = count($this->data);
		$this->data[$data_id] = $this->data_timings[$id];
		unset($this->data_timings[$a["id"]]);

		self::display($data_id);
	}


	protected function get_logline($id)
	{
		$ar = $this->data[$id];
		if (!$ar) return null;

		$b = 0;
		$modes = preg_split("/[&,|]/",$this->opts["mode"]);
		foreach($modes as $mode)
			if ($mode == "ALL" || $ar["mode"][$mode] || ($mode == "NOMODE" && !count($ar["mode"])))
				$b = 1;
		if (!$b) return null;

		$out = "";
		if (isset($ar["dt_start"]) && isset($ar["dt_end"]))
			$out .= "[" .  sprintf("%01.4f",$ar["dt_end"]-$ar["dt_start"]) . "]";
		if ($ar["mode"])
			$out .= "[" . join("|",array_keys($ar["mode"])) . "]";
		if (!$out)
			$out .= "[-]";
		$out .= " ";

		$tmp = $this->tostr($ar["data"]);

		if (preg_match("/\n/",$tmp))
			$out .= preg_replace("/\n/","\n" . str_repeat(" ",strlen($out)),$tmp);
		else
			$out .= $tmp;

		return trim($out) . "\n";
	}
	

	protected function display_logblock()
	{
		$min_dt = $max_dt = 0;
		$out = "";

		foreach($this->data as $id => $rw)
		{
			$min_dt = $this->min($min_dt,$rw["dt"],$rw["dt_start"],$rw["dt_end"]);
			$max_dt = $this->max($max_dt,$rw["dt"],$rw["dt_start"],$rw["dt_end"]);
			$out .= $this->get_logline($id);
		}

		$out = "";
		$out .= "\n<!--\n";
		$out .= "FULLTIME: " . sprintf("%01.4f",$max_dt - $min_dt) . "\n";
		$out .= trim($out);
		$out .= "\n-->\n";

		return $out;
	}


	protected function write2log($str)
	{
		if (!$this->opts["logfile"]) return;
		if ($f = fopen($this->opts["logfile"],"a"))
		{
			fwrite($f,$str);
			fclose($f);
		}
	}


	protected function min()
	{
		$args = func_get_args();
		$min = 0;
		foreach($args as $v)
			if ($v && ($min > $v || !$min)) $min = $v;
		return $min;
	}

	
	protected function max()
	{
		$args = func_get_args();
		$max = 0;
		foreach($args as $v)
			if ($v && $max < $v) $max = $v;
		return $max;
	}


	protected function t()
	{
		list($usec, $sec) = explode(" ",microtime()); return ((float)$usec + (float)$sec); 
	}


	protected function tostr($ar)
	{
		$str = "";
		foreach($ar as $v)
		{
			if (is_array($v)) $v = preg_replace("/[\r\n\t]/","",print_r($v,1));
			$out .= trim($v) . "\n";
		}
		return $out;
	}
}

