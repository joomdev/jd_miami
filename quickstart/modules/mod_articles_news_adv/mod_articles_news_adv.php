<?php
/**
 * Articles Newsflash Advanced
 *
 * @author    TemplateMonster http://www.templatemonster.com
 * @copyright Copyright (C) 2012 - 2013 Jetimpex, Inc.
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 
 * Parts of this software are based on Articles Newsflash standard module
 * 
*/

defined('_JEXEC') or die;

// Include the syndicate functions only once
require_once __DIR__ . '/helper.php';

$menu = JMenu::getInstance('site');

$app    = JFactory::getApplication(); 
$document =& JFactory::getDocument();

$list = modArticlesNewsHelper::getList($params);
$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'));
$columns = (int)$params->get('columns');
$bootstrap_layout = $params->get('bootstrap_layout');

switch ($bootstrap_layout) {
  case 0:
    $row_class = 'row';
    break;
  case 1:
    $row_class = 'row-fluid';
    break;  
  default:
    $row_class = 'row';
    break;
}

require JModuleHelper::getLayoutPath('mod_articles_news_adv', $params->get('layout', 'default'));
