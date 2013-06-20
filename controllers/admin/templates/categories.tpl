{*
*  1997-2013 QUADRA INFORMATIQUE
*
*  @author QUADRA INFORMATIQUE <modules@quadra-informatique.fr>
*  @copyright 1997-2013 QUADRA INFORMATIQUE
*  @license  http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  
*}
<ul class="treeLevel level-{$treeLevel}">
    {foreach from=$nodes item=node}
      <li class="{if isset($node.treeClass)}{$node.treeClass}{/if}" id="li_cat_id_{$node.id_category}"><a href="{$node.link}" id="cat_id_{$node.id_category}">{$node.name}</a>
          {if isset($node.subparts)}
            {$node.subparts}          
          {else}
            <ul></ul>
          {/if}
      </li>
    {/foreach}
</ul>

