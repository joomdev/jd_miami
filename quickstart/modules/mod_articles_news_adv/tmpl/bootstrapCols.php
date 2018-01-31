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

  $n = count($list);  

  if($bootstrap_layout == 0){
    switch ($columns) {
      case 1:
        $rows = $n;
        $spanClass = '';
        break;      
      default:
        $spanClass = 'span'.floor($params->get('bootstrap_size')/$columns);
        $rows = ceil($n/$columns);
        break;
    }
  } else {
      switch ($columns) {
        case 1:
          $rows = $n;
          $spanClass = '';
          break;      
        default:
          $rows = ceil($n/$columns);
          $spanClass = 'span'.floor(12/$columns);
          break;
      }
    }
?>

<div class="mod-newsflash-adv mod-newsflash-adv__<?php echo $moduleclass_sfx; ?>">

  <?php if ($params->get('pretext')): ?>
    <div class="pretext">
      <?php echo $params->get('pretext') ?>
    </div>
  <?php endif; ?>  
    
  <?php if($columns !== 1): ?>
    <div class="<?php echo $row_class; ?>">
  <?php endif; ?>

    <?php for ($i = 0, $n; $i < $n; $i ++) :
      $item = $list[$i]; 

      $class="";
      if($i == $n-1){
        $class="lastItem";
      }

      if($rows > 1 && $columns > 1 && $i !== 0 && $i % $columns == 0){
        echo '</div><div class="'. $row_class .'">';
      }
    ?>

      <div class="<?php echo $spanClass; ?> item item_num<?php echo $i; ?> item__module  <?php echo $class; ?>">
        <?php require JModuleHelper::getLayoutPath('mod_articles_news_adv', '_item'); ?>
      </div>

    <?php endfor; ?>

  <?php if($columns !== 1): ?>
    </div> 
  <?php endif; ?>

  <div class="clearfix"></div>

  <?php if($params->get('mod_button') == 1): ?>   
    <div class="mod-newsflash-adv_custom-link">
      <?php 
        $menuLink = $menu->getItem($params->get('custom_link_menu'));

          switch ($params->get('custom_link_route')) 
          {
            case 0:
              $link_url = $params->get('custom_link_url');
              break;
            case 1:
              $link_url = JRoute::_($menuLink->link.'&Itemid='.$menuLink->id);
              break;            
            default:
              $link_url = "#";
              break;
          }
          echo '<a class="btn btn-info" href="'. $link_url .'">'. $params->get('custom_link_title') .'</a>';
      ?>
    </div>
  <?php endif; ?>

</div>
