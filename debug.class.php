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

			DEBUG::log(array("message"=>"some message","mode"=>"SHORT")) - aldo should work

			DEBUG::log_start(array("id"=>"someid","message"=>array("some query",$ar1,$ar2),"mode"=>"LONG"));
			DEBUG::log_end(array("id"=>"someid","message"=>"Ok here we've done")); - also should work

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
	
	protected $data = array();		// Timings and debugs are stored here

	protected $opts = array(
		"mode" => "ALL",			// ALL, SHORT, LONG, IMPORTANT, and so on
		"modes" => "ALL,SHORT,LONG,IMPORTANT",	// List of all available modes
		"realtime" => 0,			// direct output to stdout with ob_flush and flush is turned off by default
	);

	protected $synonyms = array(
		"ls,logstart,log_start" => array("logstart_call","id,*,mode"),
		"le,logend,log_end" => array("logend_call","id,*,mode"),
		"l,log" => array("log_call","*,mode"),
		"setopt,setopts" => "setopt_call",
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


	static public function __call($m,$a)
	{
		$o = self::getInstance();

		$ar = $o->find_method($m);
		
		if ($ar["template"])
			$a = $o->parse_args($a,$ar["template"]);

		if (!method_exists(self,$ar["method"]))
			throw new Exception("Method " . $ar["method"] . ", m=" . $m . " doesn't exists in " . __CLASS__);

		$o->$m($a);
	}


	static public function display($id = null)
	{
		$o = self::getInstance();

		if (isset($id))
		{
			if ($o->opts["realtime"]))
			{
				echo $o->get_logline($id);
				ob_flush();
				flush();
			}
		}
		else
			$o->display_logblock();
	}


	protected function setopt_call()
	{
		$args = func_get_args();
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
			{
				if (is_array($v))
					return array("method"=>$v[0],"template"=>$v[1]);
				else
					return array("method"=>$v);
			}
		return array("method"=>$m);
	}


	protected function parse_args($args,$template)
	{
		$all_vars = $before_vars = $after_vars = array();
		$ar = explode(",",$template);
		$b = 0;
		foreach($ar as $v)
		{
			if ($v == "*")
			{
				$b = 1;
				continue;
			}
			elseif ($b)
				$after_vars[] = $v;
			else
				$before_vars[] = $v;
			$all_vars[] = $v;
		}

		if (is_array($args[0]))
			foreach($all_vars as $v)
				if (isset($args[0][$v])) return $args[0];

		$out = array();
		foreach($before_vars as $v)
			$out[$v] = array_pop($args);
		array_reverse($args);
		array_reverse($after_vars);
		foreach($after_vars as $v)
			$out[$v] = array_pop($args);
		array_reverse($args);

		if ($args && is_array($args))
			foreach($args as $v)
				$out["*"][] = $v;
		
		return $out;
	}


	protected function log_call($a)
	{
		$id = $this->t();
		$this->data[$id] = array("dt"=>$id,"data"=>$a["*"],"mode"=>$a["mode"]);
		$this->display($id);
	}


	protected function logstart_call($a)
	{
		if (!$a["id"]) return self::log("Incorrect opts, logstart id not found",$a);
		$this->data[$a["id"]] = array("dt_start"=>$this->t(),"data"=>$a["*"],"mode"=>$a["mode"]);
	}


	protected function logend_call($a)
	{
		if (!$ar["id"]) return self::log("Incorrect opts, logend id not found",$a);

		$this->data[$a["id"]]["dt_end"] = $this->t();
		
		if ($a["*"] && is_array($a["*"]))
			foreach($a["*"] as $v)
				$this->data[$a["id"]]["data"][] = $v;

		$this->display($ar["id"]);
	}


	protected function get_logline($id)
	{
		$ar = $this->data[$id];
		if (!$ar) return null;
		if ($ar["mode"] && $ar["mode"] != "ALL" && $this->opts["mode"] && $this->opts["mode"] != "ALL" && $ar["mode"] != $this->opts["mode"]) return null;

		$out = "\n";
		if ($ar["dt_start"] && $ar["dt_end"])
			$out .= "[" .  sprintf("%01.4f",$ar["dt_end"]-$ar["dt_start"]) . "]";

		foreach($ar["data"] as $v)
		{
			if (is_array($v)) $v = preg_replace("/[\r\t\n]/","",print_r($v,1));
			$out .= trim($v) . "\n";
		}

		return $out;
	}
	

	protected function display_logblock()
	{
		$min_dt = $this->t();
		$max_dt = 0;

		foreach($this->data as $id => $rw)
		{
			if ($rw["dt"] && $min_dt > $rw["dt"]) $min_dt = $rw["dt"];
			if ($rw["dt"] && $max_dt < $rw["dt"]) $max_dt = $rw["dt"];
			if ($rw["dt_start"] && $min_dt > $rw["dt_start"]) $min_dt = $rw["dt_start"];
			if ($rw["dt_end"] && $max_dt < $rw["dt_end"]) $max_dt = $rw["dt_end"];

			$out .= $this->get_logline($id);
		}

		echo "<!--\n";
		echo "FULLTIME: " . sprintf("%01.4f",$max_dt - $min_dt) . "\n";
		echo $out;
		echo "-->\n";
	}


	protected function t()
	{
		list($usec, $sec) = explode(" ",microtime()); return ((float)$usec + (float)$sec); 
	}
}

