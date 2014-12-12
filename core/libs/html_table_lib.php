<?php

namespace Core;

class HtmlTableLib {

	private $headers = array();
	private $rows = array();
	private $css_styles = array();
	private $style = "v";

	public function __construct($headers = null) {
		$this->addHeader($headers);
	}
	
	public function addStyle($css){
		$this->css_styles[] = $css;
	}

	public function addHeader($headers) {
		if($headers === null) {
			return;
		} elseif(is_array($headers)) {
			$this->headers = array_merge($this->headers, $headers);
		} elseif(is_string($headers)) {
			$this->headers[] = $headers;
		} else {
			return;
		}
	}

	public function addRow($values) {
		if($values === null) {
			return;
		} elseif(is_array($values)) {
			$this->rows[] = $values;
		}
	}
	
	public function output(){
		echo "<table";
		
		if(count($this->css_styles) != 0){
			echo " styles=\"";
			echo implode("; ", $this->css_styles);
			echo "\"";
		}
		echo ">\n";
		
		echo "<thead><tr>";
		foreach($this->headers as $header){
			echo "<td>";
			echo $header;
			echo "</td>";
		}
		echo "</tr></thead>";
		
		echo "<tbody>\n";
		foreach($this->rows as $row){
			echo "<tr>";
			
			foreach($row as $data){
			echo "<td>";
			echo $data;
			echo "</td>";
			}
			
			echo "</tr>\n";
		}
		echo "</tbody></table>";
	}

}
