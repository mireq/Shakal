<?php

namespace Shakal;

interface ShakalDriver
{
	public function connect($config);
	public function disconnect();
}

?>
