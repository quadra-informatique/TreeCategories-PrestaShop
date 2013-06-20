<?php
/**
 * ---------------------------------------------------------------------------------
 * 
 * 1997-2013 Quadra Informatique
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to modules@quadra-informatique.fr so we can send you a copy immediately.
 *
 * @author Quadra Informatique <modules@quadra-informatique.fr>
 * @copyright 1997-2013 Quadra Informatique
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * ---------------------------------------------------------------------------------
*/

if (!defined('_PS_VERSION_'))
	exit;

class Treecategories extends Module
{
	function __construct()
	{
		$this->name = 'treecategories';
        $this->tab = 'tree_categories';
		$this->version = 1.1;
		$this->author = 'Quadra Informatique';
		$this->need_instance = 0;
		$this->key='';

		parent::__construct();

		$this->displayName = $this->l('Tree-like categories');
		$this->description = $this->l('Allows to manage categories like tree items.');
		$this->confirmUninstall = $this->l('Are you sure you want to remove this feature ?');
	}

	function install()
	{
		if (!parent::install())
			return false;
			
		$id_lang = Language::getIdByIso('en');
		$query = '
			SELECT t.`id_tab`
			FROM `'._DB_PREFIX_.'tab` t
			LEFT JOIN `'._DB_PREFIX_.'tab_lang` tl
				ON (t.`id_tab` = tl.`id_tab` AND tl.`id_lang` = '.(int)$id_lang.')
			WHERE tl.`name` = "Catalog"
		';
        $result = Db::getInstance()->executeS($query);		
					
        $this->installModuleTab('AdminTreeCategories', array(Language::getIdByIso('fr') => 'Catégories (vue en arbre)',
                                                             Language::getIdByIso('en') => 'Categories (tree view)'), 
                                $result[0]['id_tab']);

		return true;
	}
	
	function uninstall()
	{
		if (!parent::uninstall())
			return false;

        $this->uninstallModuleTab('AdminTreeCategories');
		return true;
	}

    private function installModuleTab($tabClass, $tabName, $idTabParent)
    {
        $tab = new Tab();
        $tab->name = $tabName;
        $tab->class_name = $tabClass;
        $tab->module = $this->name;
        $tab->id_parent = (int)($idTabParent);
        if (!$tab->save())
            return false;
        return true;
    }

    private function uninstallModuleTab($tabClass)
    {
        $idTab = Tab::getIdFromClassName($tabClass);
        if ($idTab != 0)
        {
                $tab = new Tab($idTab);
                $tab->delete();
                return true;
        }
        return false;
    }

}
