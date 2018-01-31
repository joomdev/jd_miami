<?php
/**
 * @package SJ Social Media Counter 
 * @version 1.0.0
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @copyright (c) 2013 YouTech Company. All Rights Reserved.
 * @author YouTech Company http://www.smartaddons.com
 */

defined ( '_JEXEC' ) or die ();

if (!class_exists('JFormFieldSjHeading')){
	class JFormFieldSjHeading extends JFormField{
		public function getInput(){
			return '';
		}
		public function getLabel(){
			return '<label style="width: 100%; max-width: 100%; padding: 5px 0 0 0; border-bottom: solid 1px #003399; color: #003399; font-weight: bold;">' . $this->element['label'] . '</label>';
		}		
	};
}