<?php
	/**
		* -----------------------------------------------------------------------------------------------------
		*
		* DropDownMenu class 1.1
		* **********************
		*
		*
		* AUTHORING:
		* Author: Wojciech Zieli?ki, 2002
		* voyteck@caffe.com.pl
		*
		* SHORT DESCRIPTION:
		* This class allows user to generate drop-down menu for 2 levels (main menu and subitems)
		* Generating menus with more then 2 levels is possible by nesting the objects - how to do it isshown 
		* on the examples page.
		*
		* -----------------------------------------------------------------------------------------------------
		*/
		
		
		
	/* Class body ******************************************************************************************/
	class DropDownMenu {
	
	var $UpperedElement = "";
	var $DownedElement = "";
	var $RollAction = "";
	var $ElementsPositions = 0;
	var $UpperUponDropped = 1;
	
	var $MainElements = array(); 	// Main menu items
	var $SubElements = array(); 	// Submenu items
	
	var $ClassID = "";				// Unique class ID
			
	
	/**
		* Class constructor
		* UpperedElement 		: element that will be displayed while menu is uppered (string that will appear
									as the active element while menu is uppered)
		* DownedElement 		: element that will be visible while menu is downed (string that will appear as
									the active element while menu is downed)
		* RollAction 			: action on which menu will be downed / uppered
		* ElementsPositions	: how menu items will be displayed:
		*							0 - description will be used as active element
		*							1 - first the active element, then the description
		*							2 - first the description, then the active element
		* UpperUponDropped	: controls if dropped menu must be uppered after another menu has been dropped:
		*							0 - menu will not be uppered
		*							1 - menu will be uppered
		*/
	function DropDownMenu($UpperedElement = "", $DownedElement = "", $RollAction = "OnClick", $ElementsPositions = 0, $UpperUponDropped = 1) {
		$this->UpperedElement = $UpperedElement;
		$this->DownedElement = $DownedElement;
		$this->RollAction = $RollAction;
		$this->ElementsPositions = $ElementsPositions;
		$this->UpperUponDropped = $UpperUponDropped;
		$this->ObjectID = uniqid("");
	}
	
	/**
		* Adds element to menu
		* May add main menu element, as well as element for any of the submenus
		* ElementID			: unique ID for the element - cannot be same as any other element
		* Description			: description for element (string that will appear as description)
		* ParentID			: if item is any submenu's item - this should point at ElementID of main item
		*/
	function addElement($ElementID, $Description, $ParentID = "") {
		if ($ParentID == "")
			$this->MainElements[$ElementID] = $Description;
		$this->SubElements[$ParentID][$ElementID] = $Description;
	}
	
	/**
		* Generates HTML code for menu
		*/
	function generateHTML() {
		reset($this->MainElements);
		reset($this->SubElements);
		//echo "<div id='UpperedElement" . $this->ObjectID . "' style='display: none'>" . $this->UpperedElement . "</div>";
		//echo "<div id='DownedElement" . $this->ObjectID . "' style='display: none'>" . $this->DownedElement . "</div>";
		$HTMLText = "<table>";
		while (list ($mainkey, $mainval) = each ($this->MainElements)) {
			switch ($this->ElementsPositions) {
				case 0: //description used as active element
					$HTMLText .= "<tr><td id=\"menu" . $mainkey . $this->ObjectID . "\" ";
					if (is_array($this->SubElements[$mainkey]))
						$HTMLText .= $this->RollAction . "=\"roll" . $this->ObjectID . "('" . $mainkey . $this->ObjectID . "');\"";
					$HTMLText .= ">" . $this->MainElements[$mainkey] . "</td>";
					break;
				case 1: //first active element, then description
					$HTMLText .= "<tr>";
					if (is_array($this->SubElements[$mainkey])) {
						$HTMLText .= "<td id=\"menu" . $mainkey . $this->ObjectID . "\" " . $this->RollAction . "=\"roll" . $this->ObjectID . "('" . $mainkey . $this->ObjectID . "'); " . $this->AdditionalScripts . "\"><div id='element" . $mainkey . $this->ObjectID . "Uppered' style='display: auto'>" . $this->UpperedElement . "</div><div id='element" . $mainkey . $this->ObjectID . "Downed' style='display: none'>" . $this->DownedElement . "</div></td>";
					}
					else {
						$HTMLText .= "<td>&nbsp;</td>";
					}
					$HTMLText .= "<td>" . $this->MainElements[$mainkey] . "</td>";
					break;
				case 2: //first description, then active element
					$HTMLText .= "<tr><td>" . $this->MainElements[$mainkey] . "</td>";
					if (is_array($this->SubElements[$mainkey])) {
						$HTMLText .= "<td id=\"menu" . $mainkey . $this->ObjectID . "\" " . $this->RollAction . "=\"roll" . $this->ObjectID . "('" . $mainkey . $this->ObjectID . "'); " . $this->AdditionalScripts . "\"><div id='element" . $mainkey . $this->ObjectID ."Uppered' style='display: auto'>" . $this->UpperedElement . "</div><div id='element" . $mainkey . $this->ObjectID . "Downed' style='display: none'>" . $this->DownedElement . "</div></td>";
					}
					else {
						$HTMLText .= "<td>&nbsp;</td>";
					}
					break;
			}
			$HTMLText .= "</tr>\n";
			if (is_array($this->SubElements[$mainkey])) {
				$HTMLText .= "<tr><td colSpan=2 id=\"" . $mainkey . $this->ObjectID . "\" style=\"display='none';\"><table>";
				while (list ($subkey, $subval) = each ($this->SubElements[$mainkey]))
					$HTMLText .= "<tr><td>" . $this->SubElements[$mainkey][$subkey] . "</td></tr>";
				$HTMLText .= "</table></td></tr>\n";
			}
		}
		$HTMLText .= "</table>";
		return $HTMLText;
	}
	
	/**
		* Generates scripts for menus
		*/
	function generateScript() {
		$ScriptText = "<script language=javascript><!--
					function roll" . $this->ObjectID . "(object) {
						roller = document.all(object);
						if (roller.style.display == \"none\")
							{ roller.style.display = \"\"; }
						else
							{ roller.style.display = \"none\"; }";
		if ($this->ElementsPositions > 0) {
			$ScriptText .= "if (document.all(object).style.display == \"none\") 
					{ document.all(\"element\" + object + \"Uppered\").style.display = \"\"; document.all(\"element\" + object + \"Downed\").style.display = \"none\"; } 
					else { document.all(\"element\" + object + \"Uppered\").style.display = \"none\"; document.all(\"element\" + object + \"Downed\").style.display = \"\"; }";
		}
		reset($this->MainElements);
		reset($this->SubElements);
		if ($this->UpperUponDropped)
			while (list ($mainkey, $mainval) = each ($this->MainElements))
				if (is_array($this->SubElements[$mainkey])) {
					$ScriptText .= "if (document.all(object).id != \"" . $mainkey . $this->ObjectID . "\") { document.all(\"" . $mainkey . $this->ObjectID . "\").style.display = \"none\"; ";
					if ($this->ElementsPositions > 0)
						$ScriptText .= "document.all(\"" . "element" . $mainkey . $this->ObjectID . "Uppered" . "\").style.display = \"\"; document.all(\"" . "element" . $mainkey . $this->ObjectID . "Downed" . "\").style.display = \"none\";";
					$ScriptText .= "}";
				}
		$ScriptText .= "}//--></script>";
		return $ScriptText;
	}
	
	}
	
?>